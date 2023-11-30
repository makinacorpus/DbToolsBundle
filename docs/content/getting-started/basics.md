# Basics

Let's learn more about the *DbToolsBundle* with 3 use
cases that it addresses:

[[toc]]

## Use case 1 - Dealing with backup (and restore) in a delivery workflow

During your project life cycle, you will need to deploy new versions on one or
many production environments. We all know that we can't prevent problems to
happen and ideally, you always backup your database before you upgrade.

You can use `pg_dump` or `mysqldump` to do so, but why not use the *DbToolsBundle*
and let it define the correct parameters to use ? After all, Symfony already knows
all it need to connect to your database.

Juste type:

```sh
console db-tools:backup
```

With this command, you will backup your current database, but the
*DbToolsBundle* also provides a simple backups manager. At the end, it will
give you the opportunity to clean old backups from your disk.

Now, let's say that your migrations were not so robust, and something bad happenned
during your deployment, something really bad... You can't fix it quickly and you
decide that a rollback is better for now.

Run:

```sh
console db-tools:restore
```

It will list you all the backups present on your disk, and you will just have
to choose the one you want to restore!

These commands are only kinds of shortcut for `pg_dump`/`pg_restore` or
`mysqldump`/`mysql` but much more handy to use. They find the correct binaries
and execute them with the correct options for you.

## Use case 2 - Anonymizing while getting back production data on your local environment

*You need to get back data from your production environment, but you don't want to
have sensitive data on your local environment.*

Let's say you have launched a `console db-tools:backup` on your production environment, and
you got back the file on your machine.

You could run `console db-tools:restore` to populate your database with those production
data. Doing so, you will end up with sensitive data on your machine: and you don't want that.
First of all, because in most cases (for example in UE, with GDPR): that's illegal. Secondly because you just
don't want to know personnal data from your (or your client's) customers.

To avoid that, you need a proper **anonymization**. It could be tricky and time consumming to try to
nicely anonymize data: the *DbToolsBundle* get rid of that for you.

With just some PHP attributes on your Doctrine Entities, you can configure a complete anonymization
for your sensitive data.

::: info
Anonymization does not only work with Doctrine Entities. You can use it with
*any* database and [configure it with YAML](../configuration#anonymization). All you need is a DBAL connection.
:::


```php [Attribute]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

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

With this configuration, after you launch `console db-tools:anonymize`, all your user's email addresses
will be gone and replace with hashed ones.

::: tip
Learn more about how to configure Anonymization in the [dedicated section](../anonymization/essentials).
:::

Now, you have anonymized data you have previously imported. But the backup
file remains on your disk, and its sensitive data with it.

To avoid this, the *DbToolsBundle* provides a command to apply a complete *gdpr-friendly* workflow:

```sh
console db-tools:gdprify path/to/my_backup.dump
```

This will successively:

1. **import** a given backup file
2. **anonymize** the database
3. **backup** the newly anonymized database

::: warning
The last step of this workflow will **overwrite** the given backup file: this way, no sensitive
data remain on your disk.
:::

You have know a complete anonymized backup file that you can share with
your colleagues.


## Use case 3 - Having basic stats on your database without proper monitoring

In a small project, you can't always have a nice monitoring for your database. Often all you
have is a simple database client accesible throw a ssh connection. In such case, getting stats
frequently ends with copy/pasting big SQL queries in a shell prompt.

If this sounds familiar to you, try to launch:

```sh
console db-tools:stats
```

It will give you a bunch of nice stats about your database. And, honestly, this command alone could
justify you install this bundle! :relaxed:

::: tip
Read more about this command in [the dedicated section](../stats).
:::