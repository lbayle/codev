#!/bin/bash

# execute an SQL request from your PC to the mariadb container
#
# usage:
# docker_exec_sql_mantis.sh "SELECT * FROM mantis_user_table WHERE username='l-bayle'"


SQL_QUERRY=$1

#SQL_QUERRY="SELECT * FROM mantis_user_pref_table where user_id=(select id from mantis_user_table where username='l-bayle')"
#SQL_QUERRY="SELECT * FROM codev_timetracking_table WHERE date < UNIX_TIMESTAMP('2019-10-01')"

# grant Admin access for user 'l-bayle'
#UPDATE mantis_user_table SET access_level = 90 WHERE username = 'l-bayle'
#INSERT INTO codev_team_user_table (user_id, team_id, access_level, arrival_date, departure_date) VALUES ((SELECT id FROM mantis_user_table WHERE username = 'l-bayle'), '1', '10', 0, 0)

# SSH connection to the docker server
SSH_HOST="my_docker_server_IP"
SSH_USER="user"

# SQL connection to the mariadb container
DB_CONTAINER="mariadb-codevtt"
DB_USER="mantisbt"
DB_NAME="bugtracker"
DB_PASSWD="xxxxxx"


ssh ${SSH_USER}@${SSH_HOST} "docker exec ${DB_CONTAINER} /usr/bin/mariadb -h localhost -u ${DB_USER} --password=\"${DB_PASSWD}\" ${DB_NAME} -e \"${SQL_QUERRY}\""






