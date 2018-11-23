#!/bin/bash

CATEGORIES=("blues" "classical" "country" "data" "folk" "jazz" "misc" "newage" "reggae" "rock" "soundtrack")


TEMP_PATH=$([ $TMPDIR ] && echo $TMPDIR || echo "/tmp")
CURRENT_PATH=$PWD

PLATFORM=$OSTYPE



echo -n $CURRENT_PATH > "${TEMP_PATH}/cddb_db_import.tmp"

case $PLATFORM in
	*darwin*) TERM="open -nb com.apple.terminal" ;;
	*win32*|*msys*|*cygwin*) TERM="powershell.exe" ;;
	*) TERM="x-terminal-emulator -name default -e" ;;
esac

for CATEGORY in "${CATEGORIES[@]}"
do
	echo -n $CATEGORY > "${CURRENT_PATH}/.cddb_db_import"
	$(${TERM} "${CURRENT_PATH}/wscreen.sh")
	sleep 5
done