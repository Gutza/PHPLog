
  PHPLog README
  Bogdan Stancescu <bogdan@moongate.ro>, December 2002
  $Id: README,v 1.9 2003/01/23 00:23:03 bogdan Exp $

  Project homepage: http://www.moongate.ro/products/PHPLog/

  CONTENTS

  1. INSTALLING
  2. DOCUMENTATION
  3. LICENSE

  1. INSTALLING
  First delete all older PHPLog installations, if any, but stick
  to your phplog.rules file - it's fully compatible with the new
  version.
  
  Unzip in a temporary directory, and run install.sh as root.

  Configuration is performed by editing the phplog.rules - take a
  look at the contents of the sample file for documentation.

  You may set $_PL_verbosity to non-zero values for incremental
  levels of verbosity. Currently, 7 is the highest verbosity
  level which makes sense (you can set it to 100 if you wish,
  but no extra messages will be displayed). In that case, you'd
  probably want to run PLParser.php in the foreground in order
  to get the debug messages.

  If you want to check out the duration of parsing, set
  $_PL_timereport to any non-zero value ($_PL_verbosity and
  $_PL_timereport are completely unrelated).

  2. DOCUMENTATION

  IMPORTANT NOTE: Various undocumented changes have happened since
  PHPLog 0.3 - please do take a few minutes to read the documentation
  on the site. Undocumented changes include the /etc/init.d/PLParser
  script which allows you to run the parser as a service, and groups
  in order to allow you to distribute log entries to non-admin users.

  Not much documentation in here for now - check out the comments
  in phplog.rules and PLParser.php.

  Make sure you check out the project homepage (URL at the top of this file)
  from time to time for announcements and extra documentation.

  Please note the only reactions currently supported by PHPLog are
  echo, mail and exec, and only when running PLConsoleMonitor.php. The format
  for echo is "echo <style>" where <style> is a semicolon-separated list
  of display attributes. The available display attributes (which
  are only rendered correctly on VT100 terminals) are (case-insensitive):
  BOLD; HALF; UNDERLINE; BLINK; REVERSE;
  BLACK; RED; GREEN; BROWN; YELLOW; BLUE; MAGENTA; CYAN; WHITE
  ON_BLACK; ON_RED; ON_GREEN; ON_BROWN; ON_YELLOW; ON_BLUE; ON_MAGENTA;
  ON_CYAN; ON_WHITE
  (Please note that BROWN is identical to YELLOW, and ON_BROWN is
  identical to ON_YELLOW).

  These are also available, but you shouldn't need them (they're defaults):
  DEFAULT_BG; DEFAULT_FGU; DEFAULT_FG; DEFAULT; NOUNDERLINE; NORMAL;
  NOBLINK; NOREVERSE

  This project aims to become as flexible as possible. The idea is
  that PLParser.php should run at startup and parse the log files all
  the time. It just outputs the matching entries in a predetermined
  format in a temporary directory when it encounters them.

  There are several advantages to this approach:
  - SPEED: the parser takes virtually no time to parse through the
    files, so you don't have to bother with speed issues in generic
    log parsing;
  - COMPLETENESS: you don't have to have any log *monitor* running
    in order to get all the juice from your logs - the parser can run
    at all times in the background and preserve all the interesting
    messages for you to review whenever you want (even if you have
    logrotate or something similar running);
  - FLEXIBILITY: the parsing and the monitoring are completely distinct.
    This makes reporting be possible in virtually any form for the same
    ruleset: console, graphic, logged, remote.
  - DISTRIBUTED: the parser already "knows" what rule it matched, so
    the monitor doesn't have to bother about that - that information is
    provided in the temporary files.

  3. LICENSE
  This package is distributed under the GNU GPL license. You can find a
  copy of the license in the LICENSE file in the original distribution.
  
