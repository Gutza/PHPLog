#!/bin/sh

[ -e /var/run/PLParser.pid ] && exit 1

<PLPARSER_PHP>

rm -f /var/run/PLParser.pid
