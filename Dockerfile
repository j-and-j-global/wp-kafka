FROM bitnami/wordpress:5.1.1

ADD . /wp-kafka
ADD bin/entrypoint.sh /app-entrypoint.sh
