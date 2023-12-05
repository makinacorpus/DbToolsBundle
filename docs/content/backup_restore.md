# Backup and Restore

The *DbToolsBundle* comes with two Symfony Console commands to backup and restore
your database but also a tiny backups manager (`DbToolsStorage`) which handle backup
files for you.

## Backup command

The backup command will use [defined binary]('./configuration#binaries') for your database vendor with correct parameters
to dump your database.

```sh
console db-tools:backup
```
You can specify the behaviour of the command with some options:

### Connection

Per default, the command will backup the database from the default DBAL connection.

You can choose to backup a database from another connection with `--connection` option:

```sh
console db-tools:backup --connection other_connection_name
```

### Excluded tables

You may have configured [tables to be excluded in the bundle configuration](./configuration#excluded-tables).
If so, these tables will be automatically excluded each time you launch the command.

But if you want to temporary exclude some tables, run the command with the `--excluded-table` option:


```sh
# Exclude a table
console db-tools:backup --excluded-table table_to_exclude

# Or more
console db-tools:backup --excluded-table table_to_exclude_1 --excluded-table table_to_exclude_2
```

### No cleanup

Each time you launch the backup command, [backup file is store in a directory](./configuration#storage-directory).

With time, this directory will grow, that's why a [backup expiration age](./configuration#storage-directory#backup-expiration-age)
was added. Every time you launch the command, at the end, it will be asked if you want to remove obsolete
backup files (ie files that have pasted their expiration date).

If you want to skip this step, launch it with option `--no-cleanup':

```sh
console db-tools:backup --no-cleanup
```

:::warning
Note that using this option, backup files will never be cleaned up
:::

## Restore command

The restore command will use [defined binary]('./configuration#binaries') for your database vendor with correct parameters
to restore your database from existing backup files.


```sh
console db-tools:restore
```

You can specify the behaviour of the command with some options:

### Connection

Per default, the command will restore the database from the default DBAL connection.

You can choose to restore a database from another connection with `--connection` option:

```sh
console db-tools:restore --connection other_connection_name
```

### Filename

When you launch this command, existing backup files will be listed and it
will be asked you to choose one to restore.

If you want to skip this step, or if your backup file is unknown to the DbToolsStorage,
you can specify a file to restore with the `--filename` option:

```sh
console db-tools:restore --filename /path/to/my/backup.sql
```

### Force

Each time you ru this command, as it is not a sensitive operation, a confirmation will
be asked. If you want to skip it, use the `--force` option.

```sh
console db-tools:restore --force
```

:::danger
Per default, this command does not allow to restore a database when used in **production environment**.
Even if you use it with `--force`.

If you know what you are doing and want to restore a
backup in production, use the `--yes-i-am-sure-of-what-i-am-doing` option.

Note that, even with this option, the command will ask you to confirm, *twice*, that you
really want to do so.
:::

## Storage

As mentioned earlier on this page, the *DbToolsBundle* stores can list existing backup files
when you want to restore a previous one with the restore command.

All backups are stored in a directory. Per default this directory is `%kernel.project_dir%/var/db_tools`
but [it could be configured](./configuration#storage-directory).

In this directory, each backup is put in sub-directories depending on the backup date. The backup's filename
is generated from the backup date and the DBAL connection name of the database.

For a backup made the 15/05/2023 at 12:22:35 for the default connection, the filename will be :
`%kernel.project_dir%/var/db_tools/2023/05/default-20230515122235.sql`.

Note that the file extension may vary depending on the database vendor.
