#!/bin/bash
# ---------------------------------------------------
CodevTT backup script
This script backups the mantis database every day and keeps only 3 days of backups.

# add this to your crontable to backup mantis+CodevTT database
$ crontab -e
55 23 * * * /var/www/html/codevtt/tools/backup_cronjob.sh > /tmp/codevtt/reports/codevtt_cron.log

# ***** restore backup *****
#mysql --force -uroot -p<password> bugtracker < bugtracker.sql
# ---------------------------------------------------

USER=root
PASSWD=toto
DB=bugtracker

DIR_BACKUP=/tmp/codevtt/backups

DATE=$(date +%Y%m%d)
DATE_3_DAYS_AGO=$(date --date @$(( $(date +%s) - 259200 )) +%Y%m%d)

# delete the file from 3 days back, so we have always 3 days of backups
file_to_delete=${DIR_BACKUP}/${DB}_${DATE_3_DAYS_AGO}.sql
if [ -f $file_to_delete ];
then
   rm $file_to_delete
fi
if [ -f ${file_to_delete}.gz ];
then
   rm ${file_to_delete}.gz
fi

# backup
mkdir -p $DIR_BACKUP
mysqldump --opt -u${USER} -p${PASSWD} ${DB} > ${DIR_BACKUP}/${DB}_${DATE}.sql
gzip ${DIR_BACKUP}/${DB}_${DATE}.sql

# end.
