

# ports:

#export GIT_SSH=/d/proxytunnel/git_ssh_options.sh
ssh tunnel@tooping -g -L2022:github.com:22 -L2379:goserver.gokgs.com:2379 -F ssh_config


# ----------

#pour ouvrir un port en tache de fond:
#ssh tooping -N -f -L<port_local>:<goserver_ip>:<goserver_port>


#pour ouvrir ce port au reste du LAN (ma machine devient le serveur de GO)
#ssh tooping -N -f -g -L<port_local>:<goserver_ip>:<goserver_port>
#ssh lob@tooping -N -f -g -L2379:goserver.gokgs.com:2379



# non teste:
#ssh tunnel@tooping -g -L2021:ftpperso.free.fr:21 -F ssh_config





