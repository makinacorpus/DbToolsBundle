# A GDPR-friendly workflow

The *DbToolsBundle* provides a command to run a *GDPR-friendly* workflow.

The idea behind this command is to provide a way to import production data to another environment
while ensuring not keeping sensitive data.

After you got back a backup file from your production environment, run:

```sh
console db-tools:gdprify path/to/your/production/backup
```

This command successively:

1. **import** the given backup file,
2. **anonymize** the database,
3. **backup** the newly anonymized database.

::: warning
The last step of this workflow will **overwrite** the given backup file:
this way, no sensitive data remain on your disk.
:::

:::danger
It has to be noted that this workflow could be considered as problematic.

To strictly follow GDPR recommendations, **you should not download a production backup
file on your own computer**. Even if this backup file will quickly disappear from
your disk. Sensitive data should not transit on your machine.

So the next question is: why do we provide such a worflow?

Well, it is the least worst we found.

Fact is, in real life, you can't run this anonymization workflow on a production
environment. It consumes a lot of your database server ressources and if you want
to do so, you should cut your service during the operation.

A good compromise could be to perform these operations on another safe envrionment such
as your preproduction. The complete workflow will then be:

1. Run `console db-tools:backup` on your production environment
2. Securly get back the backup file to your preproduction environment
3. Run `console db-tools:backup` on your preproduction environment to
   backup the current state of your preproduction
4. Run `console db-tools:anonymize path/to/your/production/backup` to generate
   a new backup cleaned from its sensitive data
5. Restore the previous state of your preproduction with `console db-tools:restore`
6. Get back the cleaned backup to your local machine
7. Restore the backup with `console db-tools:restore --filename path/to/your/cleaned/backup`
:::
