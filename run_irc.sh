#!/bin/sh
export MAIN_PATH="/path/to/nzedb_pre_irc_bots/php"
export PHP_PATH="/usr/bin/php"
while :
do
	if ! screen -list | grep -q "serverbot"; then
		screen -dmS serverbot $PHP_PATH ${MAIN_PATH}/postIRC.php
		sleep 2
	fi

	if ! screen -list | grep -q "efnetbot"; then
		screen -dmS efnetbot $PHP_PATH ${MAIN_PATH}/scrapeIRC.php efnet
		sleep 1
	fi

	if ! screen -list | grep -q "corruptbot"; then
		screen -dmS corruptbot $PHP_PATH ${MAIN_PATH}/scrapeIRC.php corrupt
		sleep 1
	fi

	if ! screen -list | grep -q "webbot"; then
		screen -dmS webbot $PHP_PATH ${MAIN_PATH}/scrapeWEB.php
	fi

	if ! screen -list | grep -q "m2vrubot"; then
		screen -dmS m2vrubot $PHP_PATH ${MAIN_PATH}/scrapeM2VRU.php
	fi

	sleep 300
done
