##Info


There are two different parts to this git.


The first is a bot that posts PRE info to a IRC channel.
It sources the data using more bots which sit in other IRC channels fetching the data and web sources.


The second is a web page that can be queried with Efnet request ID's to return a PRE title.
This one sources it's data using bots on Efnet channels on IRC.


These are split up in case you want to run them on different servers or if you want to run only one of the two.


Using ZNC to manage your IRC servers/channels is recommend.


##IRC posting bot instructions


Create a database in MySQL: `create database ircpredb;`


From CLI, import the schema: `mysql -p ircpredb < schema_predb.sql`


This database is used to store and retrieve PRE info fetched from IRC and web sources.
You can periodically delete old rows (older than 24 hours) to save space in this database, you can write a simple bash script to do this, optimizing afterwards is recommended too.


Edit PHP/settings.php


Fill in all the settings for scrapeIRC, postIRC and scrapeWEB.


You will use the following scripts:


PHP/scrapeIRC.php : This will run bots in various IRC channels to fetch PRE info and store it in the database you created.
This script can take in arguments, `efnet` ; this will get PRE info from channels on the efnet IRC server, `corrupt` ; this will get PRE info from a channel on the corrupt IRC server.


PHP/scrapeWEB.php : This will periodically fetch PRE info from various websites and store it in the database you created.


PHP/postIRC.php : This will print out the PRE info onto a IRC channel as scrapeIRC.php and scrapeWEB.php receive them.


You can edit run_irc.sh to run these and periodically check they are still running. You can add this to a service/cron to run on boot.


This system is compatible with the nZEDb IRCscraper scripts. You can add your server to the nZEDb IRCscraper settings.php, note your channel should be named #nZEDbPRE unless you edit nZEDb to use your own channel name.


##Request ID web page instructions


Create a database in MySQL: `create database reqpredb;`


From CLI, import the schema: `mysql -p reqpredb < schema_reqid.sql`


Edit PHP/settings.php


Fill in the settings for scrapeREQ.php (near the bottom of the file).


You will run the following script:


PHP/scrapeREQ.php ; This will run bots on the Efnet IRC server to fetch Request ID's and PRE titles.


You can edit/run run_reqid.sh to run the bot and periodically check if it is running. You can add this in a service or cron job to run on boot.


You can add a "require" to your index.php file to serve Request ID lookups. Like this: `require('/path/to/PHP/WWW/reqid.php');` changing `/path/to` to where you cloned this git.


The reqid.php web page is compatible with the nZEDb Request ID lookup system. You can add the URL to the admin->site edit page.


## Tips


In the future if you want to bring in rows from the ircpredb database table to the reqpredb database table (this should only be done if you didn't run the Request ID bot and want to start using it), you can use this query (only rows that are missing will be added):

This assumes both databases are on the same MySQL server. This will not work otherwise.

`INSERT INTO reqpredb.predb (title, groupname, reqid) SELECT p.title, g.name, p.requestid FROM ircpredb.predb p INNER JOIN ircpredb.groups g ON g.id = p.groupid WHERE p.title NOT IN (SELECT title FROM reqpredb.predb);`


## License

GNU GPL v3 - since I based it on code from nZEDb.