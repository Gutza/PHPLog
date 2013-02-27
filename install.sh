#!/bin/sh

# Script to install PHPLog
# Bogdan Stancescu <bogdan@moongate.ro>, January 2003
# $Id: install.sh,v 1.2 2003/01/23 00:28:53 bogdan Exp $

# First, let's check if we have PHP
PHP=`which php 2>/dev/null`
[ -z $PHP ] && {
	echo "PHP is not installed! PHPLog doesn't work without PHP."
	echo "If you have PHP somewhere outside the path, please edit"
	echo "this script to reflect that."
	echo "(edit the line reading PHP=`which php 2>/dev/null`"
	echo "and change it to PHP=<path to your php>)"
	exit 1
}
PHPParserLine="#!$PHP -qC"

# Ok, we have PHP. Let's see where this guy wants PHPLog installed.
echo -n "Path to install to [/usr/local/PHPLog]: "
read PLPath
[ -z $PLPath ] && PLPath="/usr/local/PHPLog"
[ -d $PLPath ] || mkdir -p $PLPath || {
	echo "Directory $PLPath doesn't exist and couldn't be created."
	echo "Please create the directory manually and try again."
	exit 1
}

# All right, last check: do we have write rights in there?
[ -w $PLPath ] || {
	echo "No write permissions in $PLPath. Exiting."
	exit 1
}

# Right. Let's copy stuff.
mkdir -p ${PLPath}/store
echo $PHPParserLine > ${PLPath}/PLParser.php
cat PLParser.php >> ${PLPath}/PLParser.php
chmod 700 ${PLPath}/PLParser.php

echo $PHPParserLine > ${PLPath}/PLConsoleMonitor.php
cat PLConsoleMonitor.php >> ${PLPath}/PLConsoleMonitor.php
chmod 755 ${PLPath}/PLConsoleMonitor.php

cp PLParser_lib.php phplog.rules.example settings.php $PLPath
cp -r include $PLPath

# Ok, done with main files. Now let's fine tune.
# First, let's keep it clean in the install dir.
rm -rf ${PLPath}/CVS ${PLPath}/classes/CVS

# Now let's install the init script
cp miscel_scripts/PL_init.sh /etc/init.d/PLParser

# Next, the wrapper which will remove /var/run/PLParser.pid when the php dies
PLPARSER_PHP=`echo ${PLPath}/PLParser.php | sed s/\\\\//\\\\\\\\\\\\//g`
cat miscel_scripts/PL_wrapper.sh | sed s/\<PLPARSER_PHP\>/${PLPARSER_PHP}/g >${PLPath}/PLParser
echo "${PLPath}/PLParser.php" >> ${PLPath}/PLParser
chmod 700 ${PLPath}/PLParser

# And finally, the daemonizer, which only launches the
# wrapper in the background
PLPARSER_WRAPPER=`echo ${PLPath}/PLParser | sed s/\\\\//\\\\\\\\\\\\//g`
cat miscel_scripts/PL_daemon.sh | sed s/\<PLPARSER_WRAPPER\>/${PLPARSER_PHP}/g > /usr/local/bin/PLParser
chmod 700  /usr/local/bin/PLParser

# Let's make a link for the console monitor
ln -bs ${PLPath}/PLConsoleMonitor.php /usr/local/bin/PLConsoleMonitor

echo
echo "If you see no error messages, then your installation is successful."
echo "Now you can edit ${PLPath}/phplog.rules.example to match your"
echo "desire, and copy it to ${PLPath}/phplog.rules. Or, if you have been"
echo "running PHPLog, copy your old phplog.rules there."
echo "If you want to run the parser as a service, you can do so using"
echo "native Linux services - check your favorite init editor."
