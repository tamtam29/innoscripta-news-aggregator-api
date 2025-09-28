#!/usr/bin/env bash

if ! command -v docker-compose &>/dev/null; then
    PUID=$(id -u) PGID=$(id -g) docker compose --env-file .env exec backend_api "$@"
else
    PUID=$(id -u) PGID=$(id -g) docker-compose --env-file .env exec backend_api "$@"
fi
