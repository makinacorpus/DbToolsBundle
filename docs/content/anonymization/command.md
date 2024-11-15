---
outline:
   - 2
   - 3
---

# Anonymization command

Considering your anonymization has been configured, you can
now anonymize a backup file by running:

<div class="standalone">

```sh
vendor/bin/db-tools anonymize path/to/your/backup/to/anonymized
```

</div>
<div class="symfony">

```sh
php bin/console db-tools:anonymize path/to/your/backup/to/anonymized
```

</div>

This command will successively:

1. Backup your local database,
2. Restore the given backup file,
3. Anonymize the data from the given backup file,
4. Backup the newly anonymized database, **by overwritting the given backup file**,
5. Restore your database to its original state from the backup produced at step 1.

::: warning
The <span class="standalone">`vendor/bin/db-tools anonymize`</span><span class="symfony">`php bin/console db-tools:anonymize`</span> command alone is not enough to ensure you follow GDPR best practices.
It depends on:

* How you correctly configured your anonymization (obviously),
* Where you run this command: anonymizing a backup file means it contains
  sensitive data, hence, following GDPR recommendations, this **backup file
  should never transit on an unsecured environment**.

Learn more about a proper workflow in the [dedicated section](./workflow).
:::

## Options

You can specify the behavior of the <span class="standalone">`vendor/bin/db-tools anonymize`</span><span class="symfony">`php bin/console db-tools:anonymize`</span> command with some options detailed below.

### Anonymizing local database

The main purpose of this command is to provide a way to anonymize a backup file. But
it could also be used to anonymize local database with `--local-database`.

<div class="standalone">

```sh
vendor/bin/db-tools anonymize path/to/your/backup/to/anonymized --local-database
```

</div>
<div class="symfony">

```sh
php bin/console db-tools:anonymize path/to/your/backup/to/anonymized --local-database
```

</div>

### Do not restore initial state after anonymization

You can choose to not restore initial database with the `--no-restore` option.
With this option, steps 1 and 5 will be skipped during execution.

<div class="standalone">

```sh
vendor/bin/db-tools anonymize path/to/your/backup/to/anonymized --no-restore
```

</div>
<div class="symfony">

```sh
php bin/console db-tools:anonymize path/to/your/backup/to/anonymized --no-restore
```

</div>

### Only anonymize specific targets

Use this option if you want to anonymize only some specific targets during the process.

<div class="standalone">

```sh
vendor/bin/db-tools anonymize path/to/your/backup/to/anonymized --target target_1 --taget target_2
# or
vendor/bin/db-tools anonymize path/to/your/backup/to/anonymized -t target_1 -t target_2
```

</div>
<div class="symfony">

```sh
php bin/console db-tools:anonymize path/to/your/backup/to/anonymized --target target_1 --taget target_2
# or
php bin/console db-tools:anonymize -t target_1 -t target_2
```

</div>

::: tip
To know all your available targets, launch `db-tools:anonymization:dump-config`
:::

### Exclude targets from anonymization

Use this option if you want to exclude some specific targets from anonymization.

<div class="standalone">

```sh
vendor/bin/db-tools anonymize path/to/your/backup/to/anonymized --exclude target_1 --exclude target_2
# or
vendor/bin/db-tools anonymize path/to/your/backup/to/anonymized -x target_1 -x target_2
```

</div>
<div class="symfony">

```sh
php bin/console db-tools:anonymize path/to/your/backup/to/anonymized --exclude target_1 --exclude target_2
# or
php bin/console db-tools:anonymize -x target_1 -x target_2
```

</div>

::: tip
To know all your available targets, launch `db-tools:anonymization:dump-config`
:::

### Split update queries

By default, the anonymization process use one update query per table.
For debug purpose, it could be usefull to run not only one update query per table
but one update query per target. To do so, use the `--split-per-column` option.

::: info
Learn more about how the anonymization process builds these update queries reading
the [Internals section](./internals).
:::
