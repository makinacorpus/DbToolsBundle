FROM ubuntu:22.04

# Basic requirements
RUN apt-get update
RUN apt-get install -yqq --no-install-recommends default-mysql-client acl iproute2 zip zlib1g-dev libzip-dev \
    libxml2-dev libpng-dev libghc-curl-dev libldb-dev libldap2-dev gnupg2 libpq-dev curl ca-certificates

# Instaling postgresql-client-16
RUN echo lsb_release -cs
RUN sh -c 'echo "deb https://apt.postgresql.org/pub/repos/apt jammy-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
RUN curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc|gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg
RUN apt-get update && apt-get install -y postgresql-client-16

# Cleanup.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www
