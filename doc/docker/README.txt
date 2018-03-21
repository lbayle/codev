# ====================================================
# installing docker on the server
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
# start containers at server reboot
# ====================================================
# https://docs.docker.com/config/containers/start-containers-automatically/#restart-policy-details
# https://esalagea.wordpress.com/2016/01/21/start-a-docker-container-on-centos-at-boot-time-as-a-linux-service/

# systemctl enable docker.service
# docker run --restart always --name codevtt -h codevtt -d -p 80:80 --link mariadb lbayle/codevtt:latest
