# Louis BAYLE, 2018-10-11
# Version 0.5

# 2018-10-11: update apache 2.4.25 fur security updates
# 2020-05-24: update to PHP v7.4
# 2021-11-11: do not include phpmyadmin
# 2021-11-11: update to PHP v8.1

FROM centos:centos7
MAINTAINER Louis BAYLE <lbayle.work@gmail.com>

# ====================================================
# Container with Apache, PHP8.1, Mantis, CodevTT
# ====================================================
# https://hub.docker.com/r/lbayle/codevtt/

# ====================================================
# build
# ====================================================
# docker build --rm -t codevtt:1.8.0 .

# ====================================================
# run with docker-compose
# ====================================================

# docker-compose.yml is here:
# https://github.com/lbayle/codev/blob/master/doc/docker/docker-compose.yml

# run:
# git clone https://github.com/lbayle/codev.git /data/docker/codevtt/src
# docker-compose up -d 
# docker exec -i docker-mariadb-1 mysql -uroot -pmy_password --force bugtracker < mantis_codevtt_freshInstall.sql

# ====================================================

# Set proxy
#ENV http_proxy=http://111.222.333.444:8080
#ENV https_proxy=http://111.222.333.444:8080
#RUN sed -i '2iproxy=http:\/\/111.222.333.444:8080' /etc/yum.conf

# ------------------
# Add Epel & Remi repositories
RUN yum -y update && \
    yum -y install --setopt=tsflags=nodocs epel-release && \
    rpm -Uvh https://rpms.remirepo.net/enterprise/remi-release-7.rpm && \
    yum clean all

# ------------------
RUN rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-7 /etc/pki/rpm-gpg/RPM-GPG-KEY-remi /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-7

# ------------------
# Reinstall glibc-common for i18n.
# -> this fixes the case where only english is available in CodevTT
RUN yum -y --setopt=override_install_langs=all reinstall glibc-common && yum clean all

# ------------------
# install tools
RUN yum -y install \
        vim-enhanced \
        wget \
        git \
        git-gui \
        unzip \
        p7zip && \
    yum clean all

# ------------------
# install MySQL command-line client (for debug purpose)
#RUN yum -y --setopt=tsflags=nodocs install mariadb && yum clean all

# ------------------
# install Apache
RUN yum -y --setopt=tsflags=nodocs install \
        httpd \
        mod_ssl \
        openssl && \
    yum clean all

# Installing PHP 8.1
RUN  yum -y --enablerepo=remi-php81 install \
        php-cli \
        php \
        php-fpm \
        php-common \
        php-mysqlnd \
        php-xml \
        php-adodb \
        php-curl \
        php-gd \
        php-pecl-mcrypt \
        php-ldap \
        php-imap \
        php-soap \
        php-mbstring \
        php-pecl-memcache \
        php-pecl-memcached \
        php-pecl-zip \
        php-pecl-xdebug \
        php-pear \
        php-pdo \
        php-bcmath \
        php-process \
        php-tidy \
        php-intl && \
    yum clean all

# ------------------

# set system timezone
ENV TZ=Europe/Paris
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# set PHP timezone
RUN echo 'date.timezone=Europe/Paris' > /etc/php.d/00-docker-php-date-timezone.ini
#RUN sed -i "s/^;date.timezone =.*$/date.timezone = \"Europe\/Paris\"/" /etc/php.ini

# set PHP session lifetime (1 day)
RUN echo "session.gc_maxlifetime = 86400" > /etc/php.d/00-docker-php-session.ini

# ------------------
# install MantisBT

ENV MANTIS_VER 2.25.8
ENV MANTIS_URL https://downloads.sourceforge.net/project/mantisbt/mantis-stable/${MANTIS_VER}/mantisbt-${MANTIS_VER}.tar.gz
ENV MANTIS_FILE mantisbt-${MANTIS_VER}.tar.gz

RUN set -xe \
    && cd /var/www/html \
    && wget ${MANTIS_URL} \
    && tar -xvf ${MANTIS_FILE} \
    && rm ${MANTIS_FILE} \
    && mv mantisbt-${MANTIS_VER} mantis \
    && chown -R apache:apache mantis \
    && chmod -R g+w mantis

# ------------------
# install CodevTT

ENV CODEVTT_VER 1.8.0
ENV CODEVTT_FILE codevtt_v${CODEVTT_VER}.zip
ENV CODEVTT_URL https://github.com/lbayle/codev/releases/download/${CODEVTT_VER}/${CODEVTT_FILE}

RUN set -xe \
    && cd /var/www/html \
    && wget ${CODEVTT_URL} -O ${CODEVTT_FILE} \
    && unzip ${CODEVTT_FILE} \
    && rm ${CODEVTT_FILE} \
    && mv codevtt_v${CODEVTT_VER} codevtt \
    && chown -R apache:apache codevtt \
    && chmod -R g+w codevtt

# add mantis plugin (in case you don't mount a volume for /var/www/html/mantis/plugins)
RUN set -xe \
    && cd /var/www/html/mantis/plugins \
    && ln -s /var/www/html/codevtt/mantis_plugin/mantis_2_0/CodevTT \
    && ln -s /var/www/html/codevtt/mantis_plugin/mantis_2_0/FilterBugList \
    && mkdir -p /tmp/codevtt/logs \
    && chown -R apache:apache /tmp/codevtt

# Update httpd.conf in order to restrict access as defined by .htaccess file.
# .htaccess files are not evaluated since a default apache install does not allow to override directives in /var/www
RUN set -xe \
    && echo "#Mantis specific access policy" > /etc/httpd/conf.d/mantis.conf \
    && echo "#CodevTT specific access policy" > /etc/httpd/conf.d/codevtt.conf \
    && for f in $(find /var/www/html/mantis -name .htaccess) ; do \
    echo "<Directory $(dirname $f)>" >> /etc/httpd/conf.d/mantis.conf \
    && cat $f >> /etc/httpd/conf.d/mantis.conf \
    && echo -e "\n</Directory>\n" >> /etc/httpd/conf.d/mantis.conf \
    ; done \
    && for f in $(find /var/www/html/codevtt -name .htaccess) ; do \
    echo "<Directory $(dirname $f)>" >> /etc/httpd/conf.d/codevtt.conf \
    && cat $f >> /etc/httpd/conf.d/codevtt.conf \
    && echo -e "\n</Directory>\n" >> /etc/httpd/conf.d/codevtt.conf \
    ; done

# FIX Invalid command 'CGIPassAuth', perhaps misspelled or defined by a module not included in the server configuration
#     => centos:7 is getting too old...
RUN sed -i '/^CGIPassAuth/ s/./#&/' /etc/httpd/conf.d/mantis.conf

RUN sed -i '/^#ServerName/a ServerName codevtt:80' /etc/httpd/conf/httpd.conf

# ------------------
# Adding config files (bugtracker)
#ADD httpd_config/ssl.conf                      /etc/httpd/conf.d/ssl.conf
ADD httpd_config/php.ini                       /etc
ADD mantis_config/config_inc.php               /var/www/html/mantis/config
ADD mantis_config/custom_constants_inc.php     /var/www/html/mantis/config
ADD mantis_config/custom_relationships_inc.php /var/www/html/mantis/config
ADD mantis_config/custom_strings_inc.php       /var/www/html/mantis/config
ADD codevtt_config/config.ini                  /var/www/html/codevtt/config
ADD codevtt_config/log4php.xml                 /var/www/html/codevtt
ADD index.html                                 /var/www/html/index.html

# ------------------

EXPOSE 80
ENTRYPOINT ["/usr/sbin/httpd"]
CMD ["-D", "FOREGROUND"]
