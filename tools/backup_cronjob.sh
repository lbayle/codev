#!/bin/bash

# -----------------
# add this to your crontable tu backup mantis+CodevTT database


# ***** restore backup *****
#mysql --force -uroot -p<password> bugtracker < bugtracker.sql

# -----------------

DATE=$(date +%Y%m%d)

USER=root
PASSWD=toto
DB=bugtracker

DIR_BACKUP=/tmp/reports

mysqldump --opt -u${USER} -p${PASSWD} ${DB} > ${DIR_BACKUP}/${DB}_${DATE}.sql


# end.