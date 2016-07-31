<?php
require_once('DB.php');
require_once('IRCClient.php');
class IRCServer extends IRCClient
{
	protected $_lastPingTime = 0;
	protected $_optimizeIterations = 0;
	protected $_cleanupTime;
	protected $_box_color;
	protected $_end_color;
	protected $_inner_color;
	protected $_channels;
	protected $_ping_string;
	public $db;

	public function __construct()
	{
		$this->_debug = POST_BOT_DEBUG;
		$this->_cleanupTime = POST_BOT_CLEANUP;
		$this->_ping_string = POST_BOT_PING_STRING;
		if (POST_BOT_BOX_COLOR == '') {
			$this->_box_color = '[';
			$this->_end_color = '] ';
		} else {
			$this->_box_color = "\x03" . POST_BOT_BOX_COLOR;
			$this->_end_color = "\x0f" . $this->_box_color . "]\x0f ";
			$this->_box_color .= "[\x0f";
		}
		if (POST_BOT_INNER_COLOR == '') {
			$this->_inner_color = ' ';
		} else {
			$this->_inner_color = "\x03" . POST_BOT_INNER_COLOR . ' ';
		}
		$this->db = new nzedb\db\DB();
		$this->initiateServer();
		$this->startSniffing();
	}

	protected function initiateServer()
	{
		// Connect to IRC.
		if ($this->connect(POST_BOT_HOST, POST_BOT_PORT, POST_BOT_TLS) === false) {
			exit (
				'Error connecting to IRC!' .
				PHP_EOL
			);
		}

		if (empty($this->_ping_string)) {
			$this->_ping_string = $this->_remote_host_received;
		}

		// Login to IRC.
		if ($this->login(POST_BOT_NICKNAME, POST_BOT_REALNAME, POST_BOT_REALNAME, POST_BOT_PASSWORD) === false) {
			exit('Error logging in to IRC!' .
				PHP_EOL
			);
		}

		// Join channels.
		$this->_channels = [POST_BOT_CHANNEL => POST_BOT_CHANNEL_PASSWORD];
		if (strpos(POST_BOT_CHANNEL, ",#") !== false) {
			$this->_channels = [];
			$passwords = explode(',', POST_BOT_CHANNEL_PASSWORD);
			foreach(explode(',', POST_BOT_CHANNEL) as $key => $channel){
				$this->_channels[$channel] = (isset($passwords[$key]) ? $passwords[$key] : '');
			}
		}
		$this->joinChannels($this->_channels);

		echo '[' . date('r') . '] [Connected to IRC!]' . PHP_EOL;
	}

	protected function startSniffing()
	{
		$time = time();
		while (true)
		{
			if ($this->_optimizeIterations++ === 300) {
				$this->_optimizeIterations = 0;
				if (!empty($this->_cleanupTime)) {
					$this->db->queryExec(
						sprintf(
							'DELETE FROM predb WHERE shared = 1 AND predate < NOW() - INTERVAL %d DAY',
							$this->_cleanupTime
						)
					);
				}
				$this->db->optimise(false, 'full');
				echo PHP_EOL;
			}

			if ((time() - $this->_lastPingTime) > 60) {
				$this->_ping($this->_ping_string);
				$this->_lastPingTime = time();
			}

			$allPre = $this->db->query(
				'SELECT p.*, UNIX_TIMESTAMP(p.predate) AS ptime, groups.name AS gname FROM predb p LEFT JOIN groups ON groups.id = p.groupid WHERE p.shared in (-1, 0)'
			);
			if ($allPre) {
				$time = time();
				foreach ($allPre as $pre) {
					if ($this->formatMessage($pre)) {
						echo 'Posted [' . $pre['title'] . ']' . PHP_EOL;
						$this->db->queryExec('UPDATE predb SET shared = 1 WHERE id = ' . $pre['id']);
					} else {
						echo 'Error posting [' . $pre['title'] . ']' . PHP_EOL;
						$this->_reconnect();
						if (!$this->_connected()) {
							exit('IRC Error: The connection was lost and we could not reconnect.' . PHP_EOL);
						}
					}
					sleep(POST_BOT_POST_DELAY);
				}
			} elseif ((time() - $time > 60)) {
				$time = time();
				foreach($this->_channels as $channel => $password) {
					$this->_writeSocket('PRIVMSG ' . $channel . ' :INFO: [' . gmdate('Y-m-d H:i:s') . ' This message is to confirm I am still active.]');
				}
			}

			sleep(POST_BOT_SCAN_DELAY);
		}
	}

	protected function formatMessage($pre)
	{
		//DT: PRE Time(UTC) | TT: Title | SC: Source | CT: Category | RQ: Requestid | SZ: Size | FL: Files
		$string = '';
		if ($pre['nuked'] > 0) {
			$string .= 'NUK: ';
		} elseif ($pre['shared'] === '0') {
			$string .= 'NEW: ';
		} else {
			$string .= 'UPD: ';
		}
		$string .=
			$this->_box_color . 'DT:' . $this->_inner_color .
				gmdate('Y-m-d H:i:s', $pre['ptime']) .
			$this->_end_color .
			$this->_box_color . 'TT:' . $this->_inner_color .
				$pre['title'] .
			$this->_end_color .
			$this->_box_color . 'SC:' . $this->_inner_color .
				$pre['source'] .
			$this->_end_color .
			$this->_box_color . 'CT:' . $this->_inner_color .
				(isset($pre['category'])  ? $pre['category'] : 'N/A') .
			$this->_end_color .
			$this->_box_color . 'RQ:' . $this->_inner_color .
				((isset($pre['requestid']) && $pre['requestid'] > 0) ? $pre['requestid'] . ':' . $pre['gname'] : 'N/A') .
			$this->_end_color .
			$this->_box_color . 'SZ:' . $this->_inner_color .
				(isset($pre['size'])      ? $pre['size']     : 'N/A') .
			$this->_end_color .
			$this->_box_color . 'FL:' . $this->_inner_color .
				(isset($pre['files'])     ? $pre['files']    : 'N/A') .
			$this->_end_color .
			$this->_box_color . 'FN:' . $this->_inner_color .
				((isset($pre['filename']) && !empty($pre['filename']))  ? $pre['filename'] : 'N/A') .
			$this->_end_color;

		if (isset($pre['nuked'])) {
			switch ((int)$pre['nuked']) {
				case 0:
					break;
				case 1:
					$string .= $this->_box_color . 'UNNUKED:' . $this->_inner_color . $pre['nukereason'] . $this->_end_color;
					break;
				case 2:
					$string .= $this->_box_color . 'NUKED:' . $this->_inner_color . $pre['nukereason'] . $this->_end_color;
					break;
				case 3:
					$string .= $this->_box_color . 'MODNUKED:' . $this->_inner_color . $pre['nukereason'] . $this->_end_color;
					break;
				case 4:
					$string .= $this->_box_color . 'RENUKED:' . $this->_inner_color . $pre['nukereason'] . $this->_end_color;
					break;
				case 5:
					$string .= $this->_box_color . 'OLDNUKE:' . $this->_inner_color . $pre['nukereason'] . $this->_end_color;
					break;
			}
		}

		if (strlen($string) > 500) {
			$string = substr($string, 0, 500);
			$string .= $this->_end_color;
		}

		$success = true;
		foreach($this->_channels as $channel => $password) {
			if (!$this->_writeSocket('PRIVMSG ' . $channel . ' :' . $string)) {
				$success = false;
			}
		}
		return $success;
	}

}
