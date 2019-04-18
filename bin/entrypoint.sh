#!/usr/bin/env bash

# The following is our plugin installation
set -axe

DST="/bitnami/wordpress/wp-content/plugins/wp-kafka"

[ -d "${DST}" ] && rm -rf ${DST}
cp -R /wp-kafka "${DST}"


# The following is the bitnami entrypoint
. /opt/bitnami/base/functions
. /opt/bitnami/base/helpers

print_welcome_page

if [[ "$1" == "nami" && "$2" == "start" ]] || [[ "$1" == "httpd" ]]; then
  . /init.sh
  nami_initialize apache php mysql-client libphp wordpress
  info "Starting wordpress... "
fi

exec tini -- "$@"
