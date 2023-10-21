#!/bin/bash

# the codevtt/mantis dirs may be overridden by docker volumes
# in this case, we need to check the minimal configuration

echo "mantis-codevtt: check minimal configuration..."

mkdir -p /var/www/html/mantis/uploaded_files
mkdir -p /var/www/html/mantis/plugins
mkdir -p /var/www/html/codevtt/config
mkdir -p /tmp/codevtt/logs

if [ ! -f /var/www/html/codevtt/config/config.ini ] ; then
    echo "   - Install default codevtt/config/config.ini"
    cp /install/codevtt_config/config.ini /var/www/html/codevtt/config/config.ini
fi

if [ ! -f /var/www/html/mantis/config/config_inc.php ] ; then
    echo "   - Install default mantis/config/config_inc.php"
    cp /install/mantis_config/config_inc.php /var/www/html/mantis/config/config_inc.php
fi

if [ ! -f /var/www/html/mantis/config/custom_constants_inc.php ] ; then
    echo "   - Install default mantis/config/custom_constants_inc.php"
    cp /install/mantis_config/custom_constants_inc.php /var/www/html/mantis/config/custom_constants_inc.php
fi

if [ ! -f /var/www/html/mantis/config/custom_relationships_inc.php ] ; then
    echo "   - Install default mantis/config/custom_relationships_inc.php"
    cp /install/mantis_config/custom_relationships_inc.php /var/www/html/mantis/config/custom_relationships_inc.php
fi

if [ ! -f /var/www/html/mantis/config/custom_strings_inc.php ] ; then
    echo "   - Install default mantis/config/custom_strings_inc.php"
    cp /install/mantis_config/custom_strings_inc.php /var/www/html/mantis/config/custom_strings_inc.php
fi

if [ ! -f /var/www/html/mantis/plugins/CodevTT/CodevTT.php ] ; then
    echo "   - Installing the mantis 'CodevTT' plugin"
    cp -R /var/www/html/codevtt/mantis_plugin/mantis_2_0/CodevTT /var/www/html/mantis/plugins/CodevTT
fi

# check access rights for www-data user
DIR_TEST=/var/www/html/mantis/uploaded_files
sudo -u www-data test -w ${DIR_TEST}
if [ $? -ne 0 ] ; then
    echo "Grant www-data write permission to ${DIR_TEST}"
    chown -R www-data:www-data ${DIR_TEST} 
fi


# check access rights for www-data user
DIR_TEST=/tmp/codevtt/logs
sudo -u www-data test -w ${DIR_TEST}
if [ $? -ne 0 ] ; then
    echo "Grant www-data write permission to ${DIR_TEST}"
    chown -R www-data:www-data ${DIR_TEST} 
fi

FILE_CLASSMAP=/var/www/html/codevtt/classmap.ser
if [ -f ${FILE_CLASSMAP} ] ; then
    sudo -u www-data test -w ${FILE_CLASSMAP}
    if [ $? -ne 0 ] ; then
       echo "Grant www-data write permission to ${FILE_CLASSMAP}"
       chown www-data:www-data ${FILE_CLASSMAP} 
    fi
fi

# ---------------------------------
echo "mantis-codevtt: run apache..."
apache2-foreground
