
Start here: https://hub.docker.com/r/lbayle/codevtt/

# ====================================================
# installing docker on CentOS 7
# ====================================================
# https://docs.docker.com/install/linux/docker-ce/centos/

# yum install -y yum-utils device-mapper-persistent-data lvm2
# yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
# yum makecache fast
# yum install docker-ce docker-compose
# usermod -aG docker lbayle

# config files:

# /etc/sysconfig/docker
#   OPTIONS=" \
#           -H tcp://0.0.0.0:2375 \
#           -H unix:///var/run/docker.sock \
#           --log-level=debug \
#           "

# /etc/systemd/system/docker.service.d/http-proxy.conf
#   [Service]
#   Environment="HTTP_PROXY=http://193.56.47.8:8080/"

# /etc/sysconfig/docker-storage (empty)
# /etc/sysconfig/docker-network (empty)

# systemctl daemon-reload
# systemctl restart docker
# systemctl enable docker.service

# systemctl status docker.service
# journalctl -u docker.service

# ====================================================
# run containers
# ====================================================

docker run -e MYSQL_ROOT_PASSWORD=my_password \
           -e MYSQL_DATABASE=bugtracker \
           --name mariadb -h mariadb \
           -d --restart=unless-stopped \
           centos/mariadb:latest

docker exec -i mariadb mysql -uroot -pmy_password --force bugtracker < mantis_codevtt_freshInstall.sql

docker run -p 80:80 \
           --link mariadb \
           --name codevtt -h codevtt \
           -d --restart=unless-stopped \
           lbayle/codevtt:latest

