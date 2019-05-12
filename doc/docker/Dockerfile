# Louis BAYLE, 2018-10-11
# Version 0.4

# 2018-10-11: update apache 2.4.25 fur security updates

FROM centos:centos7
MAINTAINER Louis BAYLE <lbayle.work@gmail.com>

# ====================================================
# Container with Apache, PHP7, Mantis, CodevTT
# ====================================================
# https://hub.docker.com/r/lbayle/codevtt/

#note: You'll need one more container : centos/mariadb

# ====================================================
# build & run
# ====================================================
# docker build --rm -t codevtt:latest .

# docker run --name codevtt -h codevtt -d -p 80:80 --link mariadb lbayle/codevtt:latest
# docker run --name codevtt -h codevtt -d -p 80:80 -p 443:443 -v /etc/ssl/certs:/etc/ssl/certs --link mariadb lbayle/codevtt:latest
# docker exec -it codevtt bash

# docker run --name mariadb -h mariadb -d -e MYSQL_ROOT_PASSWORD=my_password -e MYSQL_DATABASE=bugtracker centos/mariadb:latest
# docker exec -i  mariadb mysql -uroot -pmy_password --force bugtracker < mantis_codevtt_freshInstall.sql
# ====================================================

# Set ATOS proxy
#ENV http_proxy=http://193.56.47.8:8080
#ENV https_proxy=http://193.56.47.8:8080
#RUN sed -i '2iproxy=http:\/\/193.56.47.8:8080' /etc/yum.conf

# ------------------
# Add Epel & Remi repositories
RUN yum -y update && \
    yum -y install --setopt=tsflags=nodocs epel-release && \
    rpm -Uvh http://rpms.famillecollet.com/enterprise/remi-release-7.rpm && \
    yum clean all

# ------------------
# Add CodeIT repositories (apache 2.4.25)
# https://crosp.net/blog/administration/install-latest-apache-server-centos-7/
# https://codeit.guru/en_US/
#RUN cd /etc/yum.repos.d \
#    && wget https://repo.codeit.guru/codeit.el`rpm -q --qf "%{VERSION}" $(rpm -q --whatprovides redhat-release)`.repo

# ------------------
RUN rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-7 /etc/pki/rpm-gpg/RPM-GPG-KEY-remi /etc/pki/rpm-gpg/RPM-GPG-KEY-CentOS-7

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

# Installing PHP 7.0
RUN  yum -y --enablerepo=remi-php70 install \
        php-cli \
        php \
        php-fpm \
        php-common \
        php-mysqlnd \
        php-xml \
        php-adodb \
        php-curl \
        php-gd \
        php-mcrypt \
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
        php-intl \
        phpmyadmin && \
    yum clean all

# ------------------

# set system timezone
ENV TZ=Europe/Paris
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# set PHP timezone
RUN echo 'date.timezone=Europe/Paris' > /etc/php.d/00-docker-php-date-timezone.ini
#RUN sed -i "s/^;date.timezone =.*$/date.timezone = \"Europe\/Paris\"/" /etc/php.ini

# configure phpMyAdmin (assuming DB container name is 'mariadb', see --link option)
RUN ln -s /usr/share/phpMyAdmin/ /var/www/phpmyadmin && \
    sed -i "s/localhost/mariadb/" /etc/phpMyAdmin/config.inc.php

# allow to connect to phpMyAdmin from any container/host
RUN sed -i "s/Require ip 127.0.0.1/Require ip 172.17/" /etc/httpd/conf.d/phpMyAdmin.conf && \
    sed -i "s/Allow from 127.0.0.1/Allow from 172.17/" /etc/httpd/conf.d/phpMyAdmin.conf


# ------------------
# install MantisBT

ENV MANTIS_VER 2.21.0
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

ENV CODEVTT_VER 1.4.0
ENV CODEVTT_FILE codevtt_v${CODEVTT_VER}.zip
ENV CODEVTT_URL http://codevtt.org/site/index.php?sdmon=files/${CODEVTT_FILE}

RUN set -xe \
    && cd /var/www/html \
    && wget ${CODEVTT_URL} -O ${CODEVTT_FILE} \
    && unzip ${CODEVTT_FILE} \
    && rm ${CODEVTT_FILE} \
    && mv codevtt_v${CODEVTT_VER} codevtt \
    && chown -R apache:apache codevtt \
    && chmod -R g+w codevtt

RUN set -xe \
    && cd /var/www/html/mantis/plugins \
    && ln -s /var/www/html/codevtt/mantis_plugin/mantis_2_0/CodevTT \
    && mkdir -p /tmp/codevtt/logs \
    && chown -R apache:apache /tmp/codevtt

# ------------------
# Adding config files (bugtracker)
ADD httpd_config/ssl.conf                      /etc/httpd/conf.d/ssl.conf
ADD httpd_config/php.ini                       /etc
ADD mantis_config/config_inc.php               /var/www/html/mantis/config
ADD mantis_config/custom_constants_inc.php     /var/www/html/mantis/config
ADD mantis_config/custom_relationships_inc.php /var/www/html/mantis/config
ADD mantis_config/custom_strings_inc.php       /var/www/html/mantis/config
ADD codevtt_config/config.ini                  /var/www/html/codevtt
ADD codevtt_config/log4php.xml                 /var/www/html/codevtt
ADD index.html                                 /var/www/html/index.html

# ------------------

EXPOSE 80
ENTRYPOINT ["/usr/sbin/httpd"]
CMD ["-D", "FOREGROUND"]
