
# A GDPR-friendly workflow

This bundle alone won't ensure you follow the GDPR best practices.
It will depend on how you will use it.

The key notion to understand and to keep in mind is:

> **Sensitive data should never transit on an unsecured environment**.

Here is an example of workflow - *that follows GDPR recommendations* - to retrieve anonymized
production data on your local environment.

## Prerequisites

* You have a second secured environment besides your *production* (such as a preproduction)
  and you can securely copy files from one to another,
* You can shut down your service on this second environment,
* Your anonymization is well configured: every sensitive data has been
  mapped to an anonymizer that will erase/hash/randomize it.

::: info
Note that the second environment could be any environment, not only a preproduction. All it needs to work
is the Symfony Console and a database. It doesn't need to be a complete working env.
:::

## The workflow

Let's assume the environment we have besides *production* is called *another_env*.

![The GDPR workflow](/public/gdpr-workflow.gif)

0. Run `console db-tools:backup` on *production* environment or
   choose an existing backup with `console db-tools:restore --list`,
1. Securely download your backup file from *production* to *another_env* environment,
   and stop services on *another_env* to ensure no one is using it,
2. Run `console db-tools:anonymize path/to/your/production/backup` to generate
   a new backup cleaned from its sensitive data,
3. Download the anonymized backup from *another_env* to your local machine
4. Restore the backup with `console db-tools:restore --filename path/to/your/anonymized/backup`

In the illustration above, we take the preproduction as our *another_env*. It is a kind
of universal use case: there is a preproduction environment in almost all project.

But it is important to bear in mind that **you can use whatever secured environment** you want to perform
step 2.

For example, you can automate this workflow **as a CI job** and therefore use a simple Docker container
to play the *another_env* role.

This approach has many benefits:
* You don't need to backup and restore initial state of this environment:
  the `db-tools:anonymize` will be faster,
* You can store the anonymized backup as a CI artefact, it will then be automatically available for
  all the team,
* You can run a weekly job to always have a fresh anonymized backup file.

