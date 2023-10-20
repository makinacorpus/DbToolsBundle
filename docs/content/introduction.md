# DbToolsBundle
**A set of Symfony Console Commands to interact with your database**

![Logo](../images/logo.svg)

The idea of this bundle is to simplify life for Symfony developpers when dealing with database,
anonymization and GDPR problematics.

Want to backup your database (and clean old backups)?

```sh
php bin/console db-tools:backup
```

Import an previous dump?

```sh
php bin/console db-tools:restore
```

You have just retrieved a dump from your production environment and you want
anonymize it before you share it with your colleagues?

```sh
php bin/console db-tools:grdprify
```

Or simply anonymize your current database ?

```sh
php bin/console db-tools:anonymize
```

*(ok, these last two will ask you some [configurations](/anonymization/general-concepts) :wink:)*

And finally, you want to know more about your database ?

```sh
php bin/console db-tools:stats
```
Read more about the [statistics command](/stats).
