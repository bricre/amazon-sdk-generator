#!/usr/bin/env bash
ssh-keyscan -H github.com >> ~/.ssh/known_hosts
exec "$@"