---
outline:
   - 2
   - 3
---

# Anonymization command

Considering your anonymization has been configured, you can
now anonymize a backup file by running:

```sh
console db-tools:anonymize path/to/your/backup/to/anonymized
```

This command will successively:

1. Backup your local database,
2. Restore the given backup file,
3. Anonymize the data from the given backup file,
4. Backup the newly anonymized database, **by overwritting the given backup file**,
5. Restore your database to its original state from the backup produced at step 1.

::: warning
The `db-tools:anonymize` command alone is not enough to ensure you follow GDPR best practices.
It depends on:

* How you correctly configured your anonymization (obviously),
* Where you run this command: anonymizing a backup file means it contains
  sensitive data, hence, following GDPR recommendations, this **backup file
  should never transit on an unsecured environment**.

Read the next section to learn more about a proper workflow.
:::

## A GDPR-friendly workflow

Here is an example of workflow - that follows GDPR recommendations - to retrieve anonymized production
data on your local environment.

### Prerequisites

* You have a second secured environment besides your *production* (such as a preproduction)
  and you can securely copy files from one to another,
* You can shut down your service on this second environment,
* Your anonymization is well configured: every sensitive data has been
  mapped to an anonymizer that will erase/hash/randomize it.

::: info
Note that the second environment could be any environment, not only a preproduction. All it needs to work
is the Symfony Console and a database. It doesn't need to be a complete working env.
:::

### Workflow

Let's assume the environment we have besides *production* is called *another_env*.

![The GDPR workflow](/public/gdpr-workflow.gif)

0. Run `console db-tools:backup` on *production* environment or
   choose an existing backup with `console db-tools:restore --list`,
1. Securely download your backup file from *production* to *another_env* environment,
   and stop services on *another_env* to ensure no one is using it,
1. Run `console db-tools:anonymize path/to/your/production/backup` to generate
   a new backup cleaned from its sensitive data,
2. Download the anonymized backup from *another_env* to your local machine
3. Restore the backup with `console db-tools:restore --filename path/to/your/anonymized/backup`

## Options

You can specify the behavior of the  `db-tools:anonymize`command with some options detailed below.

### Anonymizing local database

The main purpose of this command is to provide a way to anonymize a backup file. But
it could also be used to anonymize local database with `--local-database`.

```sh
console db-tools:anonymize --local-database
```

### Do not restore initial state after anonymization

You can choose to not restore initial database with the `--no-restore` option.
With this option, steps 1 and 5 will be skipped during execution.

```sh
console db-tools:anonymize --no-restore
```

### Only anonymize specific targets

Use this option if you want to anonymize only some specific targets during the process.

```sh
console db-tools:anonymize --target target_1 --taget target_2
# or
console db-tools:anonymize -t target_1 -t target_2
```

::: tip
To know all your available targets, launch `db-tools:anonymization:dump-config`
:::

### Exclude targets from anonymization

Use this option if you want to exclude some specific targets from anonymization.

```sh
console db-tools:anonymize --exclude target_1 --exclude target_2
# or
console db-tools:anonymize -x target_1 -x target_2
```

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
