#!/bin/sh
export MAIN_PATH="/path/to/nzedb_pre_irc_bots/php"
export PHP_PATH="/usr/bin/php"
while :
do

	if ! screen -list | grep -q "reqidbot"; then
		screen -dmS reqidbot $PHP_PATH ${MAIN_PATH}/scrapeREQ.php
		sleep 1
	fi

	sleep 300
done