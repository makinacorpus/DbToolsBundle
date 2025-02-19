# Database statistics

The bundle comes with a handy `db-tools:stats` command that can help you analyze
your database state and performance.

All statistics values are tagged using one of the following tags:

  - `info`: display global information,
  - `read`: read statistics,
  - `write`: write statistics,
  - `maint`: maintenance statistics, such as PostgreSQL VACUUM,
  - `code`: occasionally display SQL code, such as CREATE statements.

Per default, all commands will display values using the `info` and `read` tags.

In order to display all values, use the `--all` or `-a` switch, for example:

@@@ standalone docker
```sh
vendor/bin/db-tools stats table -a
```
@@@
@@@ symfony
```sh
php bin/console db-tools:stats table -a
```
@@@

If you want to specify only a set of tags, you may use the `--tag=TAG` or
`-t TAG` switch, this option can be specified more than once:

@@@ standalone docker
```sh
vendor/bin/db-tools stats table -t read -t write
```
@@@
@@@ symfony
```sh
php bin/console db-tools:stats table -t read -t write
```
@@@

## Table statistics

How much size takes a table on your disk ? How many rows are they ? Does your
RDBMS ran `analyze`, `vaccuum` or `optimize` enough ?

The <span db-tools-flavor="standalone docker">`vendor/bin/db-tools stats table`</span><span db-tools-flavor="symfony">`php bin/console db-tools:stats table`</span> will attempt to give you as many details about table statistics:

 - table size on disk,
 - table indices size on disk,
 - row count,
 - sequential read and index read counts (PostgreSQL only),
 - index row fetch count (PostgreSQL only),
 - maintenance tasks such as `analyze` and `vacuum` count and date (PostgreSQL only),
 - and much more information, whenever the RDBMS allows you to get it.

Simply run:

@@@ standalone docker
```sh
vendor/bin/db-tools stats table
```
@@@
@@@ symfony
```sh
php bin/console db-tools:stats table
```
@@@

Output will be ordered by table size in descending order.

## Index statistics

This command is PostgreSQL only. It gives information about all your indices
size on disk, and a few other pertinent values that may help your debugging
performance problems.

Simply run:

@@@ standalone docker
```sh
vendor/bin/db-tools stats index
```
@@@
@@@ symfony
```sh
php bin/console db-tools:stats index
```
@@@

Output will be ordered by index size in descending order.

## Global statistics

If you run the command without any arguments:

@@@ standalone docker
```sh
vendor/bin/db-tools stats
```
@@@
@@@ symfony
```sh
php bin/console db-tools:stats
```
@@@

depending upon your current database driver, you might have few or no output.

Global statistics are still being worked out, and what will show up here
may greatly vary between RDBMS implementations.
