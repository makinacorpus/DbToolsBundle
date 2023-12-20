# Basics

Let's learn more about the *DbToolsBundle* with 3 use
cases that it addresses:

[[toc]]

## Use case 1 - Dealing with backup (and restoration) in a delivery workflow

During your project life cycle, you will need to deploy new versions on one or
many production environments. We all know that we can't prevent problems to
happen, that's why, ideally, you always backup your database before upgrading.

Get this task done quickly with:

```sh
console db-tools:backup
```

*DbToolsBundle* will call the right backup program (`pg_dump`, `mysqldump` or
other) with the correct parameters. At the end of the process, it will give you
the opportunity to clean old backups from your disk.

Now, let's say that your migrations were not so robust and something bad
happened during your deployment, something really bad... You can't fix it
quickly, so you decide to rollback for now.

Simply run:

```sh
console db-tools:restore
```

This command will list you all backups available on your disk. Choose the one
you want to restore!

These commands are only kinds of shortcut for `pg_dump`/`pg_restore` or
`mysqldump`/`mysql` but much more handy to use. They find the correct binaries
and execute them with the correct options for you.

## Use case 2 - Anonymizing production data to use them on your local environment

*You need to retrieve data from your production environment, but you don't want to
have sensitive data on your local environment.*

Let's say you have launched a `console db-tools:backup` on your production environment
and downloaded the backup file on your machine.

You could run `console db-tools:restore` to populate your database from the
freshly downloaded backup file. But in doing so, you will end up with sensitive
data on your machine, which is not what you want. First of all, because in most
cases that's illegal (in UE for example, because of GDPR). Secondly because you
just don't want to know personal data from your (or your client's) customers.

To avoid that, you need a proper **anonymization**.

As it could be tricky and time-consuming to try to nicely anonymize data:
the *DbToolsBundle* get rid of that for you.

By adding some PHP attributes on your Doctrine Entities, you can easily
configure a complete anonymization for your sensitive data.

::: info
Anonymization does not only work with Doctrine Entities. You can use it with
*any* database and [configure it with YAML](../configuration#anonymization). All you need is a DBAL connection.
:::

```php [Attribute]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize; // [!code ++]

#[ORM\Entity()]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Anonymize(type: 'email')] // [!code ++]
    private ?string $emailAddress = null;

    // ...
}
```

With the above configuration, after you used `console db-tools:anonymize` on a backup file,
all your user's email addresses will be gone and replaced with hashed ones.

::: tip
Learn more about how to configure Anonymization in the [dedicated section](../anonymization/essentials).
:::

You can't directly run the anonymization on your local machine as to follow GDPR recommendations,
sensitive data should never transit on an unsecured environment.

We will therefore use the preproduction environment to perform anonymization.

```sh
console db-tools:anonymize path/to/my_backup.dump
```

You now have a fully anonymized backup file that you can download and restore on your local
environment.

::: tip
[Learn more about a good GDPR-friendly workflow](../anonymization/command#a-gdpr-friendly-workflow).
:::

## Use case 3 - Getting basic stats on your database without proper monitoring

In a small project, you can't always have a nice monitoring for your database.
Often, all you have is a simple database client accessible through an SSH
connection. In such case, getting stats frequently ends with copy/pasting big
SQL queries in a shell prompt.

If this sounds familiar to you, try to launch:

```sh
console db-tools:stats
```

It will give you a bunch of nice stats about your database. And, honestly,
this command alone could justify you install this bundle! :relaxed:

::: tip
Read more about this command in the [dedicated section](../stats).
:::
