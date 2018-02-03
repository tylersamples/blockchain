#!/usr/bin/env bash

# Accept a number of peers to spin up or default to 3.
PEERS=${1:-3}

set -o allexport
[ -f ./.env ] && source ./.env

NGINX=${8081:-$NGINX_PORT}

# Generate an array of peer names.
ITR="seq $PEERS | xargs -I{} echo "peer{}""

eval $ITR | while read MACHINE; do

  # As a very expensive call, lets check if the machine exists first and only create it if it doesn't already exist.
  if [ ! $(docker-machine status $MACHINE 2> /dev/null) ]; then
    echo "($MACHINE) Creating"
    docker-machine create --driver virtualbox $MACHINE
  fi

  # Bootstrap so were connected to the right daemon.
  echo "($MACHINE) Bootstrapping"
  eval $(docker-machine env --shell sh/bash $MACHINE)

  # Init a swarm and broadcast on the machines IP.
  echo "($MACHINE) Initializing Swarm"
  docker swarm init --advertise-addr $(docker-machine ip $MACHINE) > /dev/null

  # If we have a stack deployment, lets remove it.
  if [ $(docker stack ps "blockchain_${MACHINE}" 2> /dev/null) ]; then
        echo "($MACHINE) Removing previous stack deployment"
        docker stack rm "blockchain_$MACHINE"
  fi

  # Using the BCPATH variable as `docker-machine` on GNU/Linux is a little bit useless when it comes to sharing local dirs.
  BCPATH=$(echo $PWD | sed 's/home/hosthome/') docker stack deploy -c docker-cloud.yml "blockchain_$MACHINE"

  echo "($MACHINE) Debootstrapping"
  eval $(docker-machine env --shell sh/bash -u)

done

