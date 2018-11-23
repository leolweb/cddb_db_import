#!/bin/bash

TEMP_PATH=$([ $TMPDIR ] && echo $TMPDIR || echo "/tmp")
CURRENT_PATH=$(cat "${TEMP_PATH}/cddb_db_import.tmp")

CATEGORY=$(cat "${CURRENT_PATH}/.cddb_db_import")
SESSION_NAME=$(php -r "print(uniqid());")



cd $CURRENT_PATH

echo -n $CATEGORY > "${CURRENT_PATH}/.${SESSION_NAME}" 

screen -S $SESSION_NAME -c "${CURRENT_PATH}/screenrc"
