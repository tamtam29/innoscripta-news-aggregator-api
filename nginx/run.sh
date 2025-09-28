#!/usr/bin/env bash

if ! command -v docker-compose &>/dev/null; then
    PUID=$(id -u) PGID=$(id -g) docker compose --env-file .env "$@"
else
    PUID=$(id -u) PGID=$(id -g) docker-compose --env-file .env "$@"
fi
