#!/bin/bash

MAX_ATTEMPTS=10


TEMP_PATH=$([ $TMPDIR ] && echo $TMPDIR || echo "/tmp")
CURRENT_PATH=$(cat "${TEMP_PATH}/cddb_db_import.tmp")

CATEGORY=(${STY//./ })
CATEGORY=$(cat "${CURRENT_PATH}/.${CATEGORY[1]}")



cd $CURRENT_PATH

RETURN=1

until [ ${RETURN} -eq 0 ]; do
	php -f "${CURRENT_PATH}/cddb_db_import_parallel.php" $CATEGORY $1
	RETURN=$?
	sleep 10

	let ATTEMPTS=ATTEMPTS+1
	if [ $ATTEMPTS == $MAX_ATTEMPTS ]; then
		RETURN=0
	fi
done
