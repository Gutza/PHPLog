#!/bin/sh

trap cleanup 1 2 3 6 15

cleanup ()
{
	echo "Caught signal, exiting"
	rm -f /var/run/PLParser.pid
	exit
}

[[ $1 == '-h' ]] && {
  echo "PHPLog Parser Wrapper"
  echo "Usage:"
  echo "-f for foreground execution"
  exit
}
[[ $1 == '-f' ]] && <PLPARSER_WRAPPER>
[ -z $1 ] && <PLPARSER_WRAPPER> &

rm -f /var/run/PLParser.pid
