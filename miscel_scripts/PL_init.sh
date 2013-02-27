#!/bin/sh
#
# PHPLog       This shell script takes care of starting and stopping
#              PHPLog (a PHP log monitoring tool).
#              Actually, this script only starts/stops the PHPLog parser
#              (hence the name of this script).
#
# chkconfig: 2345 55 10
# description: PHPLog is a flexible log monitoring tool written in PHP

# Source function library.
. /etc/rc.d/init.d/functions

[ -x /usr/local/bin/PLParser ] || exit 0

prog="PHPLog parser"

# See how we were called.
case "$1" in
  start)
        # Start daemon.
        daemon /usr/local/bin/PLParser
        touch /var/lock/subsys/PLParser
        action $"Starting $prog: " /bin/true
        ;;
  stop)
        # Stop daemon.
	killproc PLParser
        rm -f /var/lock/subsys/PLParser
        action $"Stopping $prog: " /bin/true
        ;;
  status)
	status PLParser
	;;
  restart)
	$0 stop
	$0 start
	;;
  reload)
	$0 stop
	$0 start
	;;
  condrestart)
    if [ -f /var/lock/subsys/PHPLog ]; then
        $0 stop
        $0 start
    fi
    ;;
  *)
        echo "Usage: PHPLog {start|stop|restart|status}"
        exit 1
esac

exit 0
