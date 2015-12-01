<?php
require_once('DB.php');
require_once('IRCClient.php');
/**
 * Class ReqIRCScraper
 */
class ReqIRCScraper extends IRCClient
{
	/**
	 * Array of current pre info.
	 * @var array
	 * @access protected
	 */
	protected $CurPre;

	/**
	 * Run this in silent mode (no text output).
	 * @var bool
	 * @access protected
	 */
	protected $silent;

	/**
	 * @var nzedb\db\DB
	 * @access protected
	 */
	protected $db;

	/**
	 * Construct
	 *
	 * @param bool         $silent       Run this in silent mode (no text output).
	 *
	 * @access public
	 */
	public function __construct(&$silent = false)
	{
		$this->db = new nzedb\db\DB();
		$this->silent = $silent;
		$this->_debug = REQID_BOT_DEBUG;
		$this->resetPreVariables();
		$this->startScraping();
	}

	/**
	 * Main method for scraping.
	 *
	 * @access protected
	 */
	protected function startScraping()
	{
		// Connect to IRC.
		if ($this->connect(REQID_BOT_HOST, REQID_BOT_PORT, REQID_BOT_ENCRYPTION) === false) {
			exit (
				'Error connecting to (' .
				REQID_BOT_HOST .
				':' .
				REQID_BOT_PORT .
				'). Please verify your server information and try again.' .
				PHP_EOL
			);
		}

		// Login to IRC.
		if ($this->login(REQID_BOT_NICKNAME, REQID_BOT_USERNAME, REQID_BOT_REALNAME, REQID_BOT_PASSWORD) === false) {
			exit('Error logging in to: (' .
				REQID_BOT_HOST . ':' . REQID_BOT_PORT . ') nickname: (' . REQID_BOT_NICKNAME .
				'). Verify your connection information, you might also be banned from this server or there might have been a connection issue.' .
				PHP_EOL
			);
		}

		// Join channels.
		$this->joinChannels(unserialize(REQID_BOT_CHANNELS));

		if (!$this->silent) {
			echo
				'[' .
				date('r') .
				'] [Scraping of IRC channels for (' .
				REQID_BOT_HOST .
				':' .
				REQID_BOT_PORT .
				') (' .
				REQID_BOT_NICKNAME .
				') started.]' .
				PHP_EOL;
		}

		// Scan incoming IRC messages.
		$this->readIncoming();
	}

	/**
	 * Check the similarity between 2 words.
	 *
	 * @param string $word1
	 * @param string $word2
	 * @param int    $similarity
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function checkSimilarity($word1, $word2, $similarity = 49)
	{
		similar_text($word1, $word2, $percent);
		if ($percent > $similarity) {
			return true;
		}
		return false;
	}

	/**
	 * Check channel and poster, send message to right method.
	 * access protected
	 */
	protected function processChannelMessages()
	{
		$channel = strtolower($this->_channelData['channel']);
		$poster  = strtolower($this->_channelData['nickname']);

		switch($channel) {

			case '#alt.binaries.inner-sanctum':
				if ($this->checkSimilarity($poster, 'sanctum')) {
					$this->inner_sanctum();
				}
				break;

			case '#alt.binaries.erotica':
				if ($this->checkSimilarity($poster, 'ginger') || $this->checkSimilarity($poster, 'g1nger')) {
					$this->ab_erotica();
				}
				break;

			case '#alt.binaries.flac':
				if ($this->checkSimilarity($poster, 'abflac')) {
					$this->ab_flac();
				}
				break;

			case '#alt.binaries.moovee':
				if ($this->checkSimilarity($poster, 'abking')) {
					$this->ab_moovee();
				}
				break;

			case '#alt.binaries.teevee':
				if ($this->checkSimilarity($poster, 'abgod')) {
					$this->ab_teevee();
				}
				break;

			case '#alt.binaries.foreign':
				if ($this->checkSimilarity($poster, 'abqueen')) {
					$this->ab_foreign();
				}
				break;

			case '#alt.binaries.console.ps3':
				if ($this->checkSimilarity($poster, 'binarybot')) {
					$this->ab_console_ps3();
				}
				break;

			case '#alt.binaries.games.nintendods':
				if ($this->checkSimilarity($poster, 'binarybot')) {
					$this->ab_games_nintendods();
				}
				break;

			case '#alt.binaries.games.wii':
				if ($this->checkSimilarity($poster, 'binarybot') || $this->checkSimilarity($poster, 'googlebot')) {
					$this->ab_games_wii($poster);
				}
				break;

			case '#alt.binaries.games.xbox360':
				if ($this->checkSimilarity($poster, 'binarybot') || $this->checkSimilarity($poster, 'googlebot')) {
					$this->ab_games_xbox360($poster);
				}
				break;

			case '#alt.binaries.sony.psp':
				if ($this->checkSimilarity($poster, 'googlebot')) {
					$this->ab_sony_psp();
				}
				break;

			case '#scnzb':
				if ($this->checkSimilarity($poster, 'nzbs')) {
					$this->scnzb();
				}
				break;

			default:
				if ($this->checkSimilarity($poster, 'alt-bin')) {
					$this->alt_bin($channel);
				}
		}
	}

	/**
	 * Gets new PRE from #a.b.erotica
	 *
	 * @access protected
	 */
	protected function ab_erotica()
	{
		//That was awesome [*Anonymous*] Shall we do it again? ReqId:[326264] [HD-Clip] [FULL 16x50MB TeenSexMovs.14.03.30.Daniela.XXX.720p.WMV-iaK] Filenames:[iak-teensexmovs-140330] Comments:[0] Watchers:[0] Total Size:[753MB] Points Earned:[54] [Pred 3m 20s ago]
		//That was awesome [*Anonymous*] Shall we do it again? ReqId:[326663] [x264] [FULL 53x100MB Young.Ripe.Mellons.10.XXX.720P.WEBRIP.X264-GUSH] Filenames:[gush.yrmellons10] Comments:[1] Watchers:[0] Total Size:[4974MB] Points Earned:[354] [Pred 7m 5s ago] [NUKED]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[.+?\]\s+\[FULL\s+(?P<files>\d+x\d+[KMGTP]?B)\s+(?P<title>.+?)\].+?Size:\[(?P<size>.+?)\](.+?\[Pred\s+(?P<predago>.+?)\s+ago\])?(.+?\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)D\])?/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.erotica';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();

		//[NUKE] ReqId:[326663] [Young.Ripe.Mellons.10.XXX.720P.WEBRIP.X264-GUSH] Reason:[selfdupe.2014-03-09]
		} elseif (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.erotica';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.flac
	 *
	 * @access protected
	 */
	protected function ab_flac()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[42614] [FULL 10x15MB You_Blew_It-Keep_Doing_What_Youre_Doing-CD-FLAC-2014-WRE] Requested by:[*Anonymous* 21s ago] Comments:[0] Watchers:[0] Points Earned:[10] [Pred 3m 16s ago]
		if (preg_match('/Request\s+Filled!\s+ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<files>\d+x\d+[KMGTP]?B)\s+(?P<title>.+?)\].*?(\[Pred\s+(?P<predago>.+?)\s+ago\])?/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.sounds.flac';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();

		//[NUKE] ReqId:[67048] [A.Certain.Justice.2014.FRENCH.BDRip.x264-COUAC] Reason:[pred.without.proof]
		} else if (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)\]/', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.sounds.flac';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.moovee
	 *
	 * @access protected
	 */
	protected function ab_moovee()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[140445] [FULL 94x50MB Burning.Daylight.2010.720p.BluRay.x264-SADPANDA] Requested by:[*Anonymous* 3h 29m ago] Comments:[0] Watchers:[0] Points Earned:[314] [Pred 4h 29m ago]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<files>\d+x\d+[MGPTK]?B)\s+(?P<title>.+?)\]\s+.*?(\[Pred\s+(?P<predago>.+?)\s+ago\])?/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.moovee';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();

		//[NUKE] ReqId:[130274] [NOVA.The.Bibles.Buried.Secrets.2008.DVDRip.XviD-FiCO] Reason:[field.shifted_oi47.tinypic.com.24evziv.jpg]
		} else if (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)\]/', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.moovee';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.foreign
	 *
	 * @access protected
	 */
	protected function ab_foreign()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[61525] [Movie] [FULL 95x50MB Wadjda.2012.PAL.MULTI.DVDR-VIAZAC] Requested by:[*Anonymous* 5m 13s ago] Comments:[0] Watchers:[0] Points Earned:[317] [Pred 8m 27s ago]
		if (preg_match('/ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<category>.+?)\]\s+\[FULL\s+(?P<files>\d+x\d+[MGPTK]?B)\s+(?P<title>.+?)\]\s+.*?(\[Pred\s+(?P<predago>.+?)\s+ago\])?/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = 'alt.binaries.mom';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();

		//[NUKE] ReqId:[67048] [A.Certain.Justice.2014.FRENCH.BDRip.x264-COUAC] Reason:[pred.without.proof]
		} else if (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)\]/', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = 'alt.binaries.mom';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.teevee
	 *
	 * @access protected
	 */
	protected function ab_teevee()
	{
		//Thank You [*Anonymous*] Request Filled! ReqId:[183520] [FULL 19x50MB Louis.Therouxs.LA.Stories.S01E02.720p.HDTV.x264-FTP] Requested by:[*Anonymous* 53s ago] Comments:[0] Watchers:[0] Points Earned:[64] [Pred 3m 45s ago]
		if (preg_match('/Request\s+Filled!\s+ReqId:\[(?P<reqid>\d+)\]\s+\[FULL\s+(?P<files>\d+x\d+[KMGPT]?B)\s+(?P<title>.+?)\].*?(\[Pred\s+(?P<predago>.+?)\s+ago\])?/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.teevee';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();

		//[NUKE] ReqId:[183497] [From.Dusk.Till.Dawn.S01E01.720p.HDTV.x264-BATV] Reason:[bad.ivtc.causing.jerky.playback.due.to.dupe.and.missing.frames.in.segment.from.16m.to.30m]
		//[UNNUKE] ReqId:[183449] [The.Biggest.Loser.AU.S09E29.PDTV.x264-RTA] Reason:[get.samplefix]
		} else if (preg_match('/\[(?P<nuke>(MOD|OLD|RE|UN)?NUKE)\]\s+ReqId:\[(?P<reqid>\d+)\]\s+\[(?P<title>.+?)\]\s+Reason:\[(?P<reason>.+?)\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.teevee';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.console.ps3
	 *
	 * @access protected
	 */
	protected function ab_console_ps3()
	{
		//[Anonymous person filling request for: FULL 56 Ragnarok.Odyssey.ACE.PS3-iMARS NTSC BLURAY imars-ragodyace-ps3 56x100MB by Khaine13 on 2014-03-29 13:14:12][ReqID: 4888][You get a bonus of 6 for a total points earning of: 62 for filling with 10% par2s!][Your score will be adjusted once you have -filled 4888]
		if (preg_match('/\s+FULL\s+\d+\s+(?P<title>.+?)\s+(?P<files>\d+x\d+[KMGTP]?B)\s+.+?\]\[ReqID:\s+(?P<reqid>\d+)\]\[/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.console.ps3';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.games.wii
	 *
	 * @param string $poster  The name of the poster.
	 *
	 * @access protected
	 */
	protected function ab_games_wii(&$poster)
	{
		//A new NZB has been added: Go_Diego_Go_Great_Dinosaur_Rescue_PAL_WII-ZER0 PAL DVD5 zer0-gdggdr 93x50MB - To download this file: -sendnzb 12811
		if ($this->checkSimilarity($poster, 'googlebot') && preg_match('/A\s+new\s+NZB\s+has\s+been\s+added:\s+(?P<title>.+?)\s+.+?(?P<files>\d+x\d+[KMGTP]?B)\s+-\s+To.+?file:\s+-sendnzb\s+(?P<reqid>\d+)\s*/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.games.wii';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();

		//[kiczek added reason info for: Samurai_Shodown_IV_-_Amakusas_Revenge_USA_VC_NEOGEO_Wii-OneUp][VCID: 5027][Value: bad.dirname_bad.filenames_get.repack]
		} else if ($this->checkSimilarity($poster, 'binarybot') && preg_match('/added\s+(nuke|reason)\s+info\s+for:\s+(?P<title>.+?)\]\[VCID:\s+(?P<reqid>\d+)\]\[Value:\s+(?P<reason>.+?)\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.games.wii';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.games.xbox360
	 *
	 * @param string $poster  The name of the poster.
	 *
	 * @access protected
	 */
	protected function ab_games_xbox360(&$poster)
	{
		//A new NZB has been added: South.Park.The.Stick.of.Truth.PAL.XBOX360-COMPLEX PAL DVD9 complex-south.park.sot 74x100MB - To download this file: -sendnzb 19909
		if ($this->checkSimilarity($poster, 'googlebot') && preg_match('/A\s+new\s+NZB\s+has\s+been\s+added:\s+(?P<title>.+?)\s+.+?(?P<files>\d+x\d+[KMGTP]?B)\s+-\s+To.+?file:\s+-sendnzb\s+(?P<reqid>\d+)\s*/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.games.xbox360';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();

		//[egres added nuke info for: Injustice.Gods.Among.Us.XBOX360-SWAG][GameID: 7088][Value: Y]
		} else if ($this->checkSimilarity($poster, 'binarybot') && preg_match('/added\s+(nuke|reason)\s+info\s+for:\s+(?P<title>.+?)\]\[VCID:\s+(?P<reqid>\d+)\]\[Value:\s+(?P<reason>.+?)\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.games.xbox360';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.sony.psp
	 *
	 * @access protected
	 */
	protected function ab_sony_psp()
	{
		//A NZB is available: Satomi_Hakkenden_Hachitama_no_Ki_JPN_PSP-MOEMOE JAP UMD moe-satomi 69x20MB - To download this file: -sendnzb 21924
		if (preg_match('/A NZB is available:\s(?P<title>.+?)\s+.+?(?P<files>\d+x\d+[KMGPT]?B)\s+-.+?file:\s+-sendnzb\s+(?P<reqid>\d+)\s*/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.sony.psp';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.games_nintendods
	 *
	 * @access protected
	 */
	protected function ab_games_nintendods()
	{
		//NEW [NDS] PRE: Honda_ATV_Fever_USA_NDS-EXiMiUS
		if (preg_match('/NEW\s+\[NDS\]\s+PRE:\s+(?P<title>.+)/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']   = 'alt.binaries.games.nintendods';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #scnzb (boneless)
	 *
	 * @access protected
	 */
	protected function scnzb()
	{
		//[Complete][512754] Formula1.2014.Malaysian.Grand.Prix.Team.Principals.Press.Conference.720p.HDTV.x264-W4F  NZB: http://scnzb.eu/1pgOmwj
		if (preg_match('/\[Complete\]\[(?P<reqid>\d+)\]\s*(?P<title>.+?)\s+NZB:/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = 'alt.binaries.boneless';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Gets new PRE from #a.b.inner-sanctum.
	 *
	 * @access protected
	 */
	protected function inner_sanctum()
	{
		//[FILLED] [ 341953 | Emilie_Simon-Mue-CD-FR-2014-JUST | 16x79 | MP3 | *Anonymous* ] [ Pred 10m 54s ago ]
		if (preg_match('/FILLED\]\s+\[\s+(?P<reqid>\d+)\s+\|\s+(?P<title>.+?)\s+\|\s+(?P<files>\d+x\d+)\s+\|\s+(?P<category>.+?)\s+\|\s+.+?\s+\]\s+\[\s+Pred\s+(?P<predago>.+?)\s+ago\s+\]/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = 'alt.binaries.inner-sanctum';
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Get new PRE from Alt-Bin groups.
	 *
	 * @param string $channel The IRC channel name.
	 *
	 * @access protected
	 */
	protected function alt_bin(&$channel)
	{
		//Thank you<Bijour> Req Id<137732> Request<The_Blueprint-Phenomenology-(Retail)-2004-KzT *Pars Included*> Files<19> Dates<Req:2014-03-24 Filling:2014-03-29> Points<Filled:1393 Score:25604>
		if (preg_match('/Req.+?Id.*?<.*?(?P<reqid>\d+).*?>.*?Request.*?<\d{0,2}(?P<title>.+?)(\s+\*Pars\s+Included\*\d{0,2}>|\d{0,2}>)\s+Files<(?P<files>\d+)>/i', $this->_channelData['message'], $matches)) {
			$this->CurPre['source']  = str_replace('#alt.binaries', 'alt.binaries', $channel);
			$this->CurPre['title'] = $this->db->escapeString($matches['title']);
			$this->CurPre['reqid'] = $matches['reqid'];
			$this->checkForDupe();
		}
	}

	/**
	 * Check if we already have the PRE.
	 *
	 * @return bool True if we already have, false if we don't.
	 *
	 * @access protected
	 */
	protected function checkForDupe()
	{
		if ($this->db->queryOneRow(sprintf('SELECT reqid FROM predb WHERE title = %s', $this->CurPre['title'])) === false) {
			$this->insertNewPre();
		} else {
			$this->updatePre();
		}
	}

	/**
	 * Insert new PRE into the DB.
	 *
	 * @access protected
	 */
	protected function insertNewPre()
	{
		$this->db->ping(true);

		$this->db->queryExec(
			sprintf(
				'INSERT INTO predb (groupname, reqid, title) VALUES (%s, %s, %s)',
				$this->db->escapeString($this->CurPre['source']),
				$this->CurPre['reqid'],
				$this->CurPre['title']
			)
		);

		$this->doEcho(true);

		$this->resetPreVariables();
	}

	/**
	 * Updates PRE data in the DB.
	 *
	 * @access protected
	 */
	protected function updatePre()
	{
		$this->db->ping(true);

		$this->db->queryExec(
			sprintf(
				'UPDATE predb SET groupname = %s, reqid = %s WHERE title = %s',
				$this->db->escapeString($this->CurPre['source']),
				$this->CurPre['reqid'],
				$this->CurPre['title']
			)
		);

		$this->doEcho(false);

		$this->resetPreVariables();
	}

	/**
	 * Echo new or update pre to CLI.
	 *
	 * @param bool $new
	 *
	 * @access protected
	 */
	protected function doEcho($new = true)
	{
		if (!$this->silent) {

			echo
				'[' .
				date('r') .
				($new ? '] [ Added Pre ] [' : '] [Updated Pre] [') .
				$this->CurPre['source'] .
				'] [' .
				$this->CurPre['reqid'] .
				'] [' .
				$this->CurPre['title'] .
				']' .
				PHP_EOL;
		}
	}

	/**
	 * After updating or inserting new PRE, reset these.
	 *
	 * @access protected
	 */
	protected function resetPreVariables()
	{
		$this->CurPre =
			array(
				'title'    => '',
				'source'   => '',
				'reqid'    => ''
			);
	}
}
