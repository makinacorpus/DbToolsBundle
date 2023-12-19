---
outline:
   - 2
   - 3
---

# Anonymization command

Considering your anonymization has been configured, you can
now anonymize a backup file running:

```sh
console db-tools:anonymize path/to/your/backup/to/anonymized
```

This command will successively:

1. Backup your current database,
2. Restore the given backup file,
3. Anonymize the data from the given backup file,
4. Backup the newly anonymized database, **overwritting the given backup file**,
5. Restore your database to its original state from the backup done in step 1.

::: warning
The `db-tools:anonymize` command alone is not enought to ensure you follow GDPR best practices.
It depends on:

* How you correctly configured your anonymization (obviously),
* Where you run this command: you are going to anonymize a backup file,
  this backup file contains sensitive data. Thus, following GDPR recommendations
  this **backup file should never transit on an unsecured environment**.

Read the next section to learn more about a proper workflow.
:::

## A GDPR-friendly workflow

Here is an exemple of workflow - that follows GDPR recommendations - to retrieve anonymized production
data on your local environement.

### Prerequisites

* You have 2 secured environments : *production* and *env2* (such as a preproduction)
  and you can securely copy files from one to another,
* You can stop your service on preproduction,
* Your anonymization is well configured, every sensitive data has been
  mapped to an anonymizer to erased/hashed/randomized it.

::: note
Note that *env2* could be any environment, not even preproduction, all it needs is the CLI to work
and a database, it doesn't even need to be a complete working env.
:::

### Workflow

1. Run `console db-tools:backup` on your production environment or
   choose an existing backup with `console db-tools:restore --list`,
2. Securly download your backup file from your *production* to your *env2* environment,
3. Stop services on your preproduction to ensure no one is using it,
4. Run `console db-tools:anonymize path/to/your/production/backup` to generate
   a new backup cleaned from its sensitive data,
5. Restart services on your preproduction,
6. Download the anonymized backup to your local machine
7. Restore the backup with `console db-tools:restore --filename path/to/your/anonymized/backup`

## Options

You can specify the behaviour of the  `db-tools:anonymize`command with some options detailed below.

### Anonymizing current database

The main purpose of this command is to provide a way to anonymize a backup file. But
it could also be used to anonymize current database with `--current-database`.

```sh
console db-tools:anonymize --current-database
```

### Do not restore initial state after anonymization

You can choose to not restore initial database with the `--no-restore` option.
With this option, step 1 and 5 will be skipped during execution.

```sh
console db-tools:anonymize --no-restore
```

### Only anonymize specific targets

Use this option if you want to anonymize only some specific targets during the process.

```sh
console db-tools:anonymize --target target_1 --taget target_2
# or
console db-tools:anonymize --t target_1 --t target_2
```

::: tip
To know all your available targets, launch `db-tools:anonymization:dump-config`
:::

### Exclude targets from anonymization

Use this option if you want to exclude some specific targets from anonymization.

```sh
console db-tools:anonymize --exclude target_1 --exclude target_2
# or
console db-tools:anonymize --x target_1 --x target_2
```

::: tip
To know all your available targets, launch `db-tools:anonymization:dump-config`
:::

### Split update queries

By default, the anonymization process use one update query per table.
For debug purpose, it could be usefull to run not only one update query per table
but one update query per target. To do so, use the `--split-per-column` option.

::: info
Learn more about how the anonymization process build these update queries reading
the [Internals section](./internals).
:::