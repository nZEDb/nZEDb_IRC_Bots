<?php /** @noinspection HttpUrlsUsage */

// Copy to settings.php

/**
 * Settings for : scrapeIRC, postIRC, scrapeWEB, scrapeM2VRU
 * These are all the settings for the bot posting PRE info to IRC.
 */
if (defined('pre_settings')) {
	/*******************************************************************************************************************
	 * Database Settings.
	 ******************************************************************************************************************/

	/**
	 * The hostname to the MySQL server.
	 * @default '127.0.0.1'
	 */
	define('DB_HOST', '127.0.0.1');
	/**
	 * The port to the MySQL server.
	 * @default 3306
	 */
	define('DB_PORT', '3306');
	/**
	 * The name of the MySQL database.
	 * @default 'ircpredb'
	 */
	define('DB_NAME', 'ircpredb');
	/**
	 * The username for accessing the MySQL server.
	 */
	define('DB_USER', '');
	/**
	 * The password for accessing the MySQL server.
	 */
	define('DB_PASSWORD', '');

	/*******************************************************************************************************************
	 * M2VRU settings. Settings for scraping the m2v.ru RSS.
	 ******************************************************************************************************************/
	if (defined('m2v_settings')) {
		/**
		 * Total time in seconds in between scraping data from the m2v.ru RSS.
		 * @default 900
		 */
		define('M2V_SLEEP_TIME', 900);
		/**
		 * Link to the m2v.ru RSS, you can change this to filter which pre's to scrape.
		 * @default 'https://m2v.ru/?act=rss'
		 */
		define('M2VRU_RSS_LINK', 'https://m2v.ru/?act=rss');
		/**
		 * How many microseconds to wait before downloading a web page from m2v.ru
		 * This is to not hammer their website.
		 * @default 2000000
		 */
		define('M2VRU_THROTTLE_USLEEP', 2000000);
	}

	/*******************************************************************************************************************
	 * Web Settings. These are settings used to insert PRE's into your MySQL DB from web sources.
	 ******************************************************************************************************************/
	if (defined('web_settings')) {
		/**
		 * Total time in seconds in between pulls.
		 * This number is divided by the number of active sources.
		 * If you have 2 sources, 1 source will pull, the script will sleep 300 seconds, the 2nd source will pull,
		 * the script will sleep 300 seconds, etc.
		 * If you have 3 sources, then : 1st source pull , sleep 200, 2nd source pull, sleep 200, 3rd source pull,
		 * 200 sleep, etc...
		 *
		 * @default 600
		 */
		define('WEB_SLEEP_TIME', 600);
		/**
		 * Whether to fetch PRE's from SRRDB.
		 * @default false
		 */
		define('FETCH_SRRDB', false);
		/**
		 * Whether to fetch PRE's from xrel.to or not.
		 * @default false
		 */
		define('FETCH_XREL', false);
		/**
		 * Whether to fetch P2P PRE's from xrel.to or not.
		 * @default false
		 */
		define('FETCH_XREL_P2P', false);
	}

	/*******************************************************************************************************************
	 * Posting bot settings. This is for the bot that will post PRE's to IRC.
	 ******************************************************************************************************************/
	if (defined('post_bot_settings')) {
		/**
		 * You can change this to set all 3 "names" below.
		 */
		$pUsername = '';
		/**
		 * The hostname to the ZNC or IRC server.
		 * @default '127.0.0.1'
		 */
		define('POST_BOT_HOST', '127.0.0.1');
		/**
		 * The port to the ZNC or IRC server.
		 * @default '6667'
		 */
		define('POST_BOT_PORT', '6667');
		/**
		 * Are you using a IRC server that requires TLS or SSL encryption?
		 * @note Set the BOT_PORT accordingly.
		 */
		define('POST_BOT_TLS', true);
		/**
		 * Nick name, the name everyone sees in the channel.
		 */
		define('POST_BOT_NICKNAME', "$pUsername");
		/**
		 * The name people will see in /whois
		 */
		define('POST_BOT_REALNAME', "$pUsername");
		/**
		 * Used to create your hostname.
		 * @note This is the username to log in to ZNC or the IRC server.
		 */
		define('POST_BOT_USERNAME', "$pUsername");
		/**
		 * This is the server password, if you use ZNC, this is where the password goes.
		 */
		define('POST_BOT_PASSWORD', '');
		/**
		 * This is an optional string you can add to ping the server.
		 * @note On ZNC, I set this to 'ZNC', instead of pinging the main IRC server,
		 *       this will ping ZNC and ZNC will ping the main server.
		 *       This can prevent ZNC from ping timeouts.
		 * @default ''
		 */
		define('POST_BOT_PING_STRING', '');
		/**
		 * If you are having issues, turn on debug, it will show all send and received messages from and to IRC.
		 * @default false
		 */
		define('POST_BOT_DEBUG', false);
		/**
		 * This is the channel(s) to use when posting the PRE's to IRC.
		 * @note For multiple channels, seperate them by commas: '#channel1,#channel2,#channel3'
		 * @note This MUST start with a #
		 */
		define('POST_BOT_CHANNEL', '#myPREchannel');
		/**
		 * This is the channel password, if your channel requires a password.
		 * @note For multiple channels, seperate by commas 'password1,password2,password3'
		 *       If a channel has no password (in this case channel1 and channel3 have no passwords): ',password2,'
		 * @default ''
		 */
		define('POST_BOT_CHANNEL_PASSWORD', '');
		/**
		 * This is the delay in seconds to wait in between posting PRE's to the channel, if you lower this
		 * your IRC server might ban or kick you from the server.
		 * @default 2
		 */
		define('POST_BOT_POST_DELAY', 2);
		/**
		 * This is the time in seconds to wait in between checking MySQL for new PRE data to post to the IRC server.
		 * Setting this lower will use more CPU.
		 */
		define('POST_BOT_SCAN_DELAY', 10);
		/**
		 * This is the time in days to keep PRE's in MySQL, you can set it to 0 or null or false to keep them indefinitely.
		 * @default 1
		 */
		define('POST_BOT_CLEANUP', 1);
		/**
		 * To add color or not to the text boxes on IRC.
		 * ie, adding color to this: [DT: ]
		 * @note See color list lower, put in the number here. Leave '' to add no color.
		 */
		define('POST_BOT_BOX_COLOR', '');
		/**
		 * To add color or not to the text inside the boxes on IRC.
		 * ie, adding color to this: 2014-06-21 17:59:07
		 * @note See color list lower, put in the number here. Leave '' to add no color.
		 */
		define('POST_BOT_INNER_COLOR', '');
		/**
		 * Color list:
		 * 0  = White
		 * 1  = Black
		 * 2  = Navy Blue
		 * 3  = Green
		 * 4  = Red
		 * 5  = Dark Red
		 * 6  = Purple
		 * 7  = Orange
		 * 8  = Yellow
		 * 9  = Lime Green
		 * 10 = Teal
		 * 11 = Aqua Light
		 * 12 = Royal Blue
		 * 13 = Hot Pink
		 * 14 = Dark Grey
		 * 15 = Light Grey
		 */
	}

	/*******************************************************************************************************************
	 * EFNet bot settings. This is for the bot that will downloads PRE's from EFNet into your MySQL DB.
	 ******************************************************************************************************************/
	if (defined('efnet_bot_settings')) {
		/**
		 * You can change this to set all 3 "names" below.
		 */
		$eUsername = '';
		/**
		 * The hostname/IP of the IRC or ZNC server.
		 */
		define('EFNET_BOT_SERVER', '127.0.0.1');
		/**
		 * The port of the IRC or ZNC server.
		 * @default 6667
		 */
		define('EFNET_BOT_PORT', '6667');
		/**
		 * Nick name, the name everyone sees in the channel.
		 */
		define('EFNET_BOT_NICKNAME', "$eUsername");
		/**
		 * The name people will see in /whois
		 */
		define('EFNET_BOT_REALNAME', "$eUsername");
		/**
		 * Used to create your hostname.
		 * @note This is the username to log in to ZNC or the IRC server.
		 */
		define('EFNET_BOT_USERNAME', "$eUsername");
		/**
		 * The password to log in to ZNC or the IRC server.
		 * @default ''
		 */
		define('EFNET_BOT_PASSWORD', '');
		/**
		 * To use SSL or TLS encryption on the IRC or ZNC server.
		 * @default false
		 */
		define('EFNET_BOT_ENCRYPTION', false);
		/**
		 * If you are having issues, turn on debug, it will show all send and received messages from and to IRC.
		 * @default false
		 */
		define('EFNET_BOT_DEBUG', false);
		/**
		 * List of channels you can scrape.
		 * @note Add // in front of the '#channelName to block it.
		 */
		define('EFNET_BOT_CHANNELS',
			serialize(
				array(
					// Channel                             Password.
					'#alt.binaries.inner-sanctum'          => null,
					'#alt.binaries.cd.image'               => null,
					'#alt.binaries.movies.divx'            => null,
					'#alt.binaries.sounds.mp3.complete_cd' => null,
					'#alt.binaries.warez'                  => null,
					'#alt.binaries.console.ps3'            => null,
					'#alt.binaries.games.nintendods'       => null,
					'#alt.binaries.games.wii'              => null,
					'#alt.binaries.games.xbox360'          => null,
					'#alt.binaries.sony.psp'               => null,
					'#scnzb'                               => null,
					'#tvnzb'                               => null,
					// The following require passwords:
					//'#alt.binaries.teevee'                 => '',
					//'#alt.binaries.moovee'                 => '',
					//'#alt.binaries.erotica'                => '',
					//'#alt.binaries.flac'                   => '',
					//'#alt.binaries.foreign'                => '',
				)
			)
		);
	}

	/*******************************************************************************************************************
	 * Corrupt-Net bot settings. This is for the bot that will downloads PRE's from Corrupt-Net into your MySQL DB.
	 ******************************************************************************************************************/
	if (defined('corrupt_bot_settings')) {
		/**
		 * You can change this to set all 3 "names" below.
		 */
		$cUsername = '';
		/**
		 * The hostname/IP of the IRC or ZNC server.
		 * @note The hostname for corrupt-net is irc.corrupt-net.org
		 */
		define('CORRUPT_BOT_HOST', '127.0.0.1');
		/**
		 * The port of the IRC or ZNC server.
		 * @default 6667
		 */
		define('CORRUPT_BOT_PORT', '6667');
		/**
		 * Nick name, the name everyone sees in the channel.
		 */
		define('CORRUPT_BOT_NICKNAME', "$cUsername");
		/**
		 * The name people will see in /whois
		 */
		define('CORRUPT_BOT_REALNAME', "$cUsername");
		/**
		 * Used to create your hostname.
		 * @note This is the username to log in to ZNC or the IRC server.
		 */
		define('CORRUPT_BOT_USERNAME', "$cUsername");
		/**
		 * The password to log in to ZNC or the IRC server.
		 * @default ''
		 */
		define('CORRUPT_BOT_PASSWORD', '');
		/**
		 * To use SSL or TLS encryption on the IRC or ZNC server.
		 * @default false
		 */
		define('CORRUPT_BOT_ENCRYPTION', false);
		/**
		 * If you are having issues, turn on debug, it will show all send and received messages from and to IRC.
		 * @default false
		 */
		define('CORRUPT_BOT_DEBUG', false);
	}
}

/**
 * Settings for scrapeREQ.php
 * These are all the settings for the web page for searching Request ID's.
 */
else if (defined('req_settings')) {
	/*******************************************************************************************************************
	 * Database Settings.
	 ******************************************************************************************************************/

	/**
	 * The hostname to the MySQL server.
	 * @default '127.0.0.1'
	 */
	define('DB_HOST', '');
	/**
	 * The port to the MySQL server.
	 * @default 3306
	 */
	define('DB_PORT', '3306');
	/**
	 * The name of the MySQL database.
	 * @default 'reqpredb'
	 */
	define('DB_NAME', 'reqpredb');
	/**
	 * The username for accessing the MySQL server.
	 */
	define('DB_USER', '');
	/**
	 * The password for accessing the MySQL server.
	 */
	define('DB_PASSWORD', '');
	/**
	 * Optional sock file location.
	 * @optional
	 * @default ''
	 */
	define('DB_SOCKET', '');

	/*******************************************************************************************************************
	 * EFNet bot settings. This is for the bot that will downloads PRE's from EFNet into your MySQL DB. For Request ID's
	 ******************************************************************************************************************/

	/**
	 * You can change this to set all 3 "names" below.
	 */
	$username = '';
	/**
	 * The hostname/IP of the IRC or ZNC server.
	 */
	define('REQID_BOT_HOST', '127.0.0.1');
	/**
	 * The port of the IRC or ZNC server.
	 * @default 6667
	 */
	define('REQID_BOT_PORT', '6667');
	/**
	 * Nick name, the name everyone sees in the channel.
	 */
	define('REQID_BOT_NICKNAME', "$username");
	/**
	 * The name people will see in /whois
	 */
	define('REQID_BOT_REALNAME', "$username");
	/**
	 * Used to create your hostname.
	 * @note This is the username to log in to ZNC or the IRC server.
	 */
	define('REQID_BOT_USERNAME', "$username");
	/**
	 * The password to log in to ZNC or the IRC server.
	 * @default ''
	 */
	define('REQID_BOT_PASSWORD', '');
	/**
	 * To use SSL or TLS encryption on the IRC or ZNC server.
	 * @default false
	 */
	define('REQID_BOT_ENCRYPTION', false);
	/**
	 * If you are having issues, turn on debug, it will show all send and received messages from and to IRC.
	 * @default false
	 */
	define('REQID_BOT_DEBUG', false);
	/**
	 * List of channels you can scrape.
	 * @note Add // in front of the '#channelName to block it.
	 */
	define('REQID_BOT_CHANNELS',
		serialize(
			array(
				// Channel                             Password.
				'#alt.binaries.inner-sanctum'          => null,
				'#alt.binaries.cd.image'               => null,
				'#alt.binaries.movies.divx'            => null,
				'#alt.binaries.sounds.mp3.complete_cd' => null,
				'#alt.binaries.warez'                  => null,
				'#alt.binaries.console.ps3'            => null,
				'#alt.binaries.games.nintendods'       => null,
				'#alt.binaries.games.wii'              => null,
				'#alt.binaries.games.xbox360'          => null,
				'#alt.binaries.sony.psp'               => null,
				'#scnzb'                               => null,
				'#tvnzb'                               => null,
				// The following require passwords:
				//'#alt.binaries.teevee'                 => '',
				//'#alt.binaries.moovee'                 => '',
				//'#alt.binaries.erotica'                => '',
				//'#alt.binaries.flac'                   => '',
				//'#alt.binaries.foreign'                => '',
			)
		)
	);
}
