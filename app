#!/usr/bin/env bash

NO_FORWARD=(
    up
    start
    restart
    down
    stop
)

if [[ " ${NO_FORWARD[*]} " =~ " $1 " ]]; then
    docker compose "$@"
else
    docker compose exec -it app "$@"
fi
