#!/bin/bash
set -e
BASEDIR=$(cd "$(dirname "$0")" && pwd)
HOSTIP=${HOSTIP:-127.0.0.1}
$BASEDIR/run.py --rabbitmq_ip="$HOSTIP" --php_ver=8 --hook_extension=uopz "$@"
