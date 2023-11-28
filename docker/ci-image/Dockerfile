FROM ubuntu:22.04

# Basic requirements
RUN apt-get update
RUN apt-get install -yqq --no-install-recommends mysql-client-core-8.0 acl iproute2 zip zlib1g-dev libzip-dev \
    libxml2-dev libpng-dev libghc-curl-dev libldb-dev libldap2-dev gnupg2 libpq-dev curl ca-certificates

# Instaling postgresql-client-16
RUN echo lsb_release -cs
RUN sh -c 'echo "deb https://apt.postgresql.org/pub/repos/apt jammy-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
RUN curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc|gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg
RUN apt-get update && apt-get install -y postgresql-client-16

# SQL Server support
ENV ACCEPT_EULA=Y
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
RUN curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
RUN apt-get update
RUN apt-get -y --no-install-recommends --allow-downgrades install msodbcsql18 odbcinst=2.3.7 odbcinst1debian2=2.3.7 \
    unixodbc-dev=2.3.7 unixodbc=2.3.7

# Cleanup.
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www
