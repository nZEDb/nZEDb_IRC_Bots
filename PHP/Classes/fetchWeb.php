<?php

use nzedb\db\DB;

require_once('DB.php');

class fetchWeb
{
	/**
	 * @var DB
	 */
	protected DB $_db;

	protected int $_done = 0;

	/**
	 * Number of active Web Sources.
	 */
	protected int $_activeSources = 0;

	/**
	 * The sleep time in between sources is totalSleepTime divided by activeSources.
	 * @var float
	 */
	protected float $_sleepTime;

	public function __construct()
	{
		if (FETCH_SRRDB) { $this->_activeSources++; }
		if (FETCH_XREL) { $this->_activeSources++; }
		if (FETCH_XREL_P2P) { $this->_activeSources++; }
		if (!$this->_activeSources) {
			sleep(WEB_SLEEP_TIME);
			return;
		}
		$this->_sleepTime = (WEB_SLEEP_TIME / $this->_activeSources);
		$this->_db = new DB();
		$this->_start();
	}

	protected function _start()
	{
		while(true) {
			if (FETCH_SRRDB) {
				$this->_retrieveSrr();
			}
			if (FETCH_XREL) {
				$this->_retrieveXrel();
			}
			if (FETCH_XREL_P2P) {
				$this->_retrieveXrelP2P();
			}
		}
	}

	protected function _echoDone(): void
    {
		echo "Fetched $this->_done PREs. Sleeping $this->_sleepTime seconds.\n";
		$this->_done = 0;
		sleep($this->_sleepTime);
	}

	/**
	 * Get pre from SrrDB.
	 */
	protected function _retrieveSrr(): void
    {
		echo "Fetching SrrDB\n";
        /** @noinspection HttpUrlsUsage */
        $data = $this->_getUrl("http://www.srrdb.com/feed/srrs");
		if ($data !== false) {
			$data = @simplexml_load_string($data);
			if ($data !== false) {
				$this->_db->ping(true);
				foreach ($data->channel->item as $release) {
					$result = array();
					$result['title'] = $release->title;
					$result['date'] = strtotime($release->pubDate);
					$result['source'] = 'srrdb';
					$this->_verifyPreData($result);
				}
				$this->_echoDone();
				return;
			}
		}
		echo "Update from Srr failed.\n";
	}

	/**
	 * Get pre from Xrel.
	 */
	protected function _retrieveXrel(): void
    {
		echo "Fetching Xrel\n";
		$data = $this->_getUrl("https://api.xrel.to/v2/release/latest.json?per_page=100");
		if ($data !== false) {
			$data = json_decode($data);
			if ($data) {
				$this->_db->ping(true);
				foreach ($data->list as $release) {
					$result = array();
					$result['title'] = trim($release->dirname);
					$result['date'] = trim($release->time);
					$result['source'] = 'xrel';
					if (isset($release->size->number) && isset($release->size->unit)) {
						$result['size'] = trim($release->size->number) . trim($release->size->unit);
					}
					$this->_verifyPreData($result);
				}
				$this->_echoDone();
				return;
			}
		}
		echo "Update from Xrel failed.\n";
	}

	/**
	 * Get pre from XrelP2P.
	 */
	protected function _retrieveXrelP2P(): void
    {
		echo "Fetching XrelP2P\n";
		$data = $this->_getUrl("https://api.xrel.to/v2/p2p/releases.json?per_page=100");
		if ($data !== false) {
			$data = json_decode($data);
			if ($data) {
				$this->_db->ping(true);
				foreach ($data->list as $release) {
					$result = array();
					$result['title'] = trim($release->dirname);
					$result['date'] = trim($release->pub_time);
					$result['source'] = 'xrelp2p';
					if (isset($release->size_mb)) {
						$result['size'] = trim($release->size_mb) . "MB";
					}
					if (isset($release->category->meta_cat) && isset($release->category->sub_cat)) {
						$result['category'] = ucfirst(trim($release->category->meta_cat)) . " " . trim($release->category->sub_cat);
					}
					$this->_verifyPreData($result);
				}
				$this->_echoDone();
				return;
			}
		}
		echo "Update from XrelP2P failed.\n";
	}

	protected function _verifyPreData(&$matches): void
    {
		// If the title is too short, don't bother.
		if (strlen($matches['title']) < 15) {
			return;
		}

		$matches['title'] = str_replace(array("\r", "\n"), '', $matches['title']);

		$duplicateCheck = $this->_db->queryOneRow(
			sprintf('SELECT * FROM predb WHERE title = %s', $this->_db->escapeString($matches['title']))
		);

		if (!is_numeric($matches['date']) || $matches['date'] < (time() - 31536000)){
			return;
		}

		if ($duplicateCheck === false) {
			$this->_db->queryExec(
				sprintf('
					INSERT INTO predb (title, size, category, predate, source, requestid, groupid, files, filename, nuked, nukereason, shared)
					VALUES (%s, %s, %s, %s, %s, %d, %d, %s, %s, %d, %s, -1)',
					$this->_db->escapeString($matches['title']),
					(!empty($matches['size']) ? $this->_db->escapeString($matches['size']) : 'NULL'),
					(!empty($matches['category']) ? $this->_db->escapeString($matches['category']) : 'NULL'),
					$this->_db->from_unixtime($matches['date']),
					$this->_db->escapeString($matches['source']),
					((isset($matches['requestid']) && is_numeric($matches['requestid']) ? $matches['requestid'] : 0)),
					((isset($matches['groupid']) && is_numeric($matches['groupid'])) ? $matches['groupid'] : 0),
					(!empty($matches['files']) ? $this->_db->escapeString($matches['files']) : 'NULL'),
					(isset($matches['filename']) ? $this->_db->escapeString($matches['filename']) : $this->_db->escapeString('')),
					((isset($matches['nuked']) && is_numeric($matches['nuked'])) ? $matches['nuked'] : 0),
					(!empty($matches['nukereason']) ? $this->_db->escapeString($matches['nukereason']) : 'NULL')
				)
			);
			$this->_done++;
		} else {
			$query = 'UPDATE predb SET ';

			$query .= $this->_updateString('size', $duplicateCheck['size'], $matches['size']);
			$query .= $this->_updateString('files', $duplicateCheck['files'], $matches['files']);
			$query .= $this->_updateString('nukereason', $duplicateCheck['nukereason'], $matches['reason']);
			$query .= $this->_updateString('requestid', $duplicateCheck['requestid'], $matches['requestid'], false);
			$query .= $this->_updateString('groupid', $duplicateCheck['groupid'], $matches['groupid'], false);
			$query .= $this->_updateString('nuked', $duplicateCheck['nuked'], $matches['nuked'], false);
			$query .= $this->_updateString('filename', $duplicateCheck['filename'], $matches['filename']);
			$query .= $this->_updateString('category', $duplicateCheck['category'], $matches['category']);

			if ($query === 'UPDATE predb SET ') {
				return;
			}

			$this->_done++;

			$query .= ('predate = ' . $this->_db->from_unixtime($matches['date']) . ', ');
			$query .= ('source = ' . $this->_db->escapeString($matches['source']) . ', ');
			$query .= ('title = ' . $this->_db->escapeString($matches['title']));
			$query .= ', shared = -1';
			$query .= (' WHERE title = ' . $this->_db->escapeString($matches['title']));

			$this->_db->queryExec($query);
		}
	}

	protected function _updateString($sqlKey, $oldValue, $newValue, $escape = true): string
    {
		return ((empty($oldValue) && !empty($newValue))
			? ($sqlKey . ' = ' . ($escape ? $this->_db->escapeString($newValue) : $newValue) . ', ')
			: ''
		);
	}

	/**
	 * Use cURL To download a web page into a string.
	 *
	 * @param string $url       The URL to download.
	 * @param string $method    get/post
	 * @param string $postdata  If using POST, post your POST data here.
	 * @param string $language  Use alternate language in header.
	 * @param bool $debug       Show debug info.
	 * @param string $userAgent User agent.
	 * @param string $cookie    Cookie.
	 *
	 * @return string|bool
     */
	protected function &_getUrl(
        string $url,
        string $method = 'get',
        string $postdata = '',
        string $language = 'en',
        bool   $debug = false,
        string $userAgent = 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10',
        string $cookie = 'foo=bar'): string|bool
    {
        $language = match ($language) {
            'fr', 'fr-fr' => "fr-fr",
            'de', 'de-de' => "de-de",
            'en' => 'en',
            default => "en-us",
        };
		$header[] = "Accept-Language: " . $language;

		$ch      = curl_init();
		$options = array(
			CURLOPT_URL            => $url,
			CURLOPT_HTTPHEADER     => $header,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT        => 15,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		);
		curl_setopt_array($ch, $options);

		if ($userAgent !== '') {
			curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		}

		if ($cookie !== '') {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}

		if ($method === 'post') {
			$options = array(
				CURLOPT_POST       => 1,
				CURLOPT_POSTFIELDS => $postdata
			);
			curl_setopt_array($ch, $options);
		}

		if ($debug) {
			$options =
				array(
					CURLOPT_HEADER      => true,
					CURLINFO_HEADER_OUT => true,
					CURLOPT_NOPROGRESS  => false,
					CURLOPT_VERBOSE     => true
				);
			curl_setopt_array($ch, $options);
		}

		$buffer = curl_exec($ch);
		$err    = curl_errno($ch);
		curl_close($ch);

		if ($err !== 0) {
			$buffer = false;
		}
		return $buffer;
	}
}
