<?php /** @noinspection HttpUrlsUsage */

define('pre_settings', true);
define('m2v_settings', true);
require('settings.php');
require_once('Classes/DB.php');

const M3VRU_HTTP_HOST = 'http://m2v.ru';
const M3VRU_HTTPS_HOST = 'https://m2v.ru';

$db = new nzedb\db\DB();

for (;;) {
	$db->ping(true);
	$rss_data = getUrl(M2VRU_RSS_LINK);
	if (!$rss_data) {
		echo "Error downloading '" . M2VRU_RSS_LINK . "'\n";
		sleep_printout(M2V_SLEEP_TIME);
		continue;
	}

    /** @var false|SimpleXMLElement $rss_data */
	$rss_data = @simplexml_load_string($rss_data);
	if (!$rss_data) {
		echo "Error parsing XML data from M2V RSS.\n";
		sleep_printout(M2V_SLEEP_TIME);
		continue;
	}

	echo "Downloaded RSS data from M2V.\n";
	$items = 0;
	foreach ($rss_data->channel->item as $item) {
		if ($item->title == "m2v.ru") {
			continue;
		}

		if ($db->queryOneRow("SELECT id FROM predb WHERE filename != '' AND title = " . $db->escapeString($item->title))) {
			continue;
		}

        $link = str_starts_with($item->link, M3VRU_HTTP_HOST)
            ? str_replace(M3VRU_HTTP_HOST, M3VRU_HTTPS_HOST, $item->link)
            : $item->link;

		$item_data = getUrl($link);
		if (!$item_data) {
			echo "Error downloading page: '$item->title'\n$link\nSkipping.\n";
			usleep(M2VRU_THROTTLE_USLEEP);
			continue;
		}

		$fileName = $alternateFileName = '';
		if (preg_match_all('#<DIV\s+class=links>(.+?)</DIV>#is', $item_data, $matches)) {
			foreach ($matches[1] as $match) {
				// <b>b8zkcy01.zip, <font color="silver">size: <font color="white">4,77 MB</font>
				if (preg_match('#<b>\s*(?P<filename>.+?)\s*,#is', $match, $matches2)) {
					if (preg_match('#\.(nfo|sfv|mu3|txt|jpe?g|png|gif)$#', $matches2['filename'])) {
						$alternateFileName = $matches2['filename'];
						continue;
					}
					$fileName = $matches2['filename'];
					break;
				}
			}
		}

		if (!$fileName) {
			if ($alternateFileName) {
				$fileName = $alternateFileName;
			} else {
				echo "Could not find file name for '$item->title'.\nSkipping.\n";
				usleep(M2VRU_THROTTLE_USLEEP);
				continue;
			}
		} else {
			echo "Found $fileName for '$item->title', updating PreDB table.\n";
		}

		$item->title = $db->escapeString($item->title);
		$item->category = $db->escapeString($item->category);
		$item->pubDate = strtotime($item->pubDate);
		$fileName = $db->escapeString(preg_replace('#\..{0,5}$#', '', $fileName));
		$db->queryInsert(
			sprintf(
				"INSERT INTO predb (title, category, predate, filename, source)
				VALUES (%s, %s, FROM_UNIXTIME(%d), %s, 'm2v.ru')
				ON DUPLICATE KEY
				UPDATE title = %s, category = %s, predate = FROM_UNIXTIME(%d), filename = %s, source = 'm2v.ru', id = LAST_INSERT_ID(id)",
				$item->title, $item->category, $item->pubDate, $fileName,
				$item->title, $item->category, $item->pubDate, $fileName
			)
		);

		$items++;
		echo "Sleeping " . M2VRU_THROTTLE_USLEEP . " microseconds to be kind on m2v.ru\n";
		usleep(M2VRU_THROTTLE_USLEEP);
	}

	echo "Updated $items rows in PreDB\n";

	sleep_printout(M2V_SLEEP_TIME);
}

function sleep_printout(int $time): void
{
	$time--;
	if (!$time) {
		echo "\n";
		return;
	}
	echo "Sleeping $time seconds.\r";
	sleep(1);
	sleep_printout($time);
}

/**
 * Use cURL To download a web page into a string.
 *
 * @param string $url       The URL to download.
 * @param bool $debug     Show debug info.
 *
 * @return bool|string
 */
function getUrl(string $url, bool $debug = false): bool|string
{
	$ch      = curl_init();
	$options = array(
		CURLOPT_URL            => $url,
		CURLOPT_HTTPHEADER     => ["Accept-Language: en-us"],
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_TIMEOUT        => 15,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_COOKIE         => 'foo=bar',
		CURLOPT_USERAGENT      => 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10'
	);
	curl_setopt_array($ch, $options);

	if ($debug) {
		$options = [
			CURLOPT_HEADER      => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_NOPROGRESS  => false,
			CURLOPT_VERBOSE     => true
		];
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