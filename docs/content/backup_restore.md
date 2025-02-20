# Back up and Restore

*DbToolsBundle* comes with two commands to back up and restore your database
but also a tiny backups manager which handle backup files for you.

## Backup command

The backup command will use the [predefined or configured binary](./configuration/basics#binaries) for your
database vendor with correct parameters to dump your database.

Each time you launch the backup command, [a backup file is stored in a directory](./configuration/basics#storage-directory) (See
[Storage section](#storage) below for more information on how backup files are stored).

With time, this directory will grow, that's why a [backup expiration age](./configuration/basics#storage-directory#backup-expiration-age)
was added. Every time you launch the command, at the end, it will be asked if you want to remove obsolete
backup files (i.e. files that have passed their expiration date).

@@@ standalone
```sh
vendor/bin/db-tools backup
```
@@@
@@@ symfony
```sh
php bin/console db-tools:backup
```
@@@
@@@ docker
```sh
docker compose run dbtools backup
```
@@@

You can specify the behavior of the command with some options detailed below.

### Connection

@@@ standalone
By default, the command will back up the database from the default connection.

If you configured several ones, you can choose to back up a database from a specific
connection with `--connection` option:

```sh
vendor/bin/db-tools backup --connection other_connection_name
```
@@@
@@@ symfony
By default, the command will back up the database from the default DBAL connection.

You can choose to back up a database from another connection with `--connection` option:

```sh
php bin/console db-tools:backup --connection other_connection_name
```
@@@
@@@ docker
By default, the command will back up the database from the default connection.

If you configured several ones, you can choose to back up a database from a specific
connection with `--connection` option:

```sh
docker compose run dbtools --connection other_connection_name
```
@@@

### Excluded tables

You may have configured [tables to be exclude in the bundle configuration](./configuration/basics#excluded-tables).
If so, these tables will be automatically excluded each time you launch the command.

But if you want to temporarily exclude some tables, run the command with the `--excluded-table` option:

@@@ standalone
```sh
# Exclude a table
vendor/bin/db-tools backup --excluded-table table_to_exclude

# Or more
vendor/bin/db-tools backup --excluded-table table_to_exclude_1 --excluded-table table_to_exclude_2
```
@@@
@@@ symfony
```sh
# Exclude a table
php bin/console db-tools:backup --excluded-table table_to_exclude

# Or more
php bin/console db-tools:backup --excluded-table table_to_exclude_1 --excluded-table table_to_exclude_2
```
@@@
@@@ docker
```sh
# Exclude a table
docker compose run dbtools backup --excluded-table table_to_exclude

# Or more
docker compose run dbtools backup --excluded-table table_to_exclude_1 --excluded-table table_to_exclude_2
```
@@@

### No cleanup

If you want to skip the cleanup step, launch it with option `--no-cleanup':

@@@ standalone
```sh
vendor/bin/db-tools backup --no-cleanup
```
@@@
@@@ symfony
```sh
php bin/console db-tools:backup --no-cleanup
```
@@@
@@@ docker
```sh
docker compose run dbtools backup --no-cleanup
```
@@@

:::warning
Note that using this option, backup files will never be cleaned up.
:::

### Extra options

If you need to occasionally provide some custom options to the backup binary,
use the `--extra-options` (`-o`) option in your command:

@@@ standalone
```sh
vendor/bin/db-tools backup --extra-options "--opt1 val1 --opt2 val2 --flag"
```
@@@
@@@ symfony
```sh
php bin/console db-tools:backup --extra-options "--opt1 val1 --opt2 val2 --flag"
```
@@@
@@@ docker
```sh
docker compose run dbtools backup --extra-options "--opt1 val1 --opt2 val2 --flag"
```
@@@

Unless you specify the `--ignore-default-options` option, the custom options
will be added to the [default options](./configuration/basics#default-binary-options).

### Ignoring default options

If necessary, [default options](./configuration/basics#default-binary-options) can be
ignored for a backup by using the `--ignore-default-options` option:

@@@ standalone
```sh
# Will run a backup without any special options except essential ones:
vendor/bin/db-tools backup --ignore-default-options
```
@@@
@@@ symfony
```sh
# Will run a backup without any special options except essential ones:
php bin/console db-tools:backup --ignore-default-options
```
@@@
@@@ docker
```sh
# Will run a backup without any special options except essential ones:
docker compose run dbtools backup --ignore-default-options
```
@@@

## Restore command

The restore command will use [predefined or configured binary](./configuration/basics#binaries) for your database vendor with correct parameters
to restore your database from an existing backup files.

@@@ standalone
```sh
vendor/bin/db-tools restore
```
@@@
@@@ symfony
```sh
php bin/console db-tools:restore
```
@@@
@@@ docker
```sh
docker compose run dbtools restore
```
@@@

You can specify the behavior of the command with some options detailed below.

### Connection

@@@ standalone
By default, the command will restore the database from the default connection.

If you configured several ones, you can choose to restore a database from a specific
connection with `--connection` option:

```sh
vendor/bin/db-tools restore --connection other_connection_name
```
@@@
@@@ symfony
By default, the command will restore the database from the default DBAL connection.

You can choose to restore a database from another connection with `--connection` option:

```sh
php bin/console db-tools:restore --connection other_connection_name
```
@@@
@@@ docker
By default, the command will restore the database from the default connection.

If you configured several ones, you can choose to restore a database from a specific
connection with `--connection` option:

```sh
docker compose run dbtools restore --connection other_connection_name
```
@@@

### Filename

When you launch this command, existing backup files will be listed and it
will be asked you to choose the one to restore.

If you want to skip this step, or if your backup file is unknown to the storage
manager, you can specify a file to restore with the `--filename` option:

@@@ standalone
```sh
vendor/bin/db-tools restore --filename /path/to/my/backup.sql
```
@@@
@@@ symfony
```sh
php bin/console db-tools:restore --filename /path/to/my/backup.sql
```
@@@
@@@ docker
```sh
docker compose run dbtools restore --filename /path/to/my/backup.sql
```
@@@

### Force

Each time you run this command, as it is a sensitive operation, a confirmation will
be asked. If you want to skip it, use the `--force` option.


@@@ standalone
```sh
vendor/bin/db-tools restore --force
```
@@@
@@@ symfony
```sh
php bin/console db-tools:restore --force
```
@@@
@@@ docker
```sh
docker compose run dbtools restore --force
```
@@@

:::danger
By default, this command does not allow to restore a database when used in **production environment**.
Even if you use it with `--force`.

If you know what you are doing and want to restore a
backup in production, use the `--yes-i-am-sure-of-what-i-am-doing` option.

Note that, even with this option, the command will ask you to confirm, *twice*, that you
really want to do so.
:::

### Extra options

If you need to occasionally provide some custom options to the restoration
binary, use the `--extra-options` (`-o`) option in your command:

@@@ standalone
```sh
vendor/bin/db-tools restore --extra-options "--opt1 val1 --opt2 val2 --flag"
```
@@@
@@@ symfony
```sh
php bin/console db-tools:restore --extra-options "--opt1 val1 --opt2 val2 --flag"
```
@@@
@@@ docker
```sh
docker compose run dbtools restore --extra-options "--opt1 val1 --opt2 val2 --flag"
```
@@@

Unless you specify the `--ignore-default-options` option, the custom options
will be added to the [default options](./configuration/basics#default-binary-options).

### Ignoring default options

If necessary, [default options](./configuration/basics#default-binary-options) can be
ignored for a restoration by using the `--ignore-default-options` option:

@@@ standalone
```sh
# Will run a restoration without any special options except essential ones:
vendor/bin/db-tools restore --ignore-default-options
```
@@@
@@@ symfony
```sh
# Will run a restoration without any special options except essential ones:
php bin/console db-tools:restore --ignore-default-options
```
@@@
@@@ docker
```sh
docker compose run dbtools restore --ignore-default-options
```
@@@


## Storage

As mentioned earlier on this page, *DbToolsBundle* can list existing backup files
when you want to restore a previous one with the restore command.

All backups are stored in a directory. By default this directory is  <span db-tools-flavor="standalone docker">`./var/db_tools` (relative to the yaml config file)</span><span db-tools-flavor="symfony">`%kernel.project_dir%/var/db_tools`</span>
but [you can choose the directory you want](./configuration/basics#storage-directory).

In this directory, each backup is put in sub-directories depending on the backup date. The backup's filename
is generated from the backup date and the DBAL connection name of the database.

For a backup made the 2023-05-15 at 12:22:35 for the default connection, the filename will be :
<span db-tools-flavor="standalone docker">`./var/db_tools/2023/05/default-20230515122235.sql`</span><span db-tools-flavor="symfony">`%kernel.project_dir%/var/db_tools/2023/05/default-20230515122235.sql`</span>.

Note that the file extension may vary depending on the database vendor.
