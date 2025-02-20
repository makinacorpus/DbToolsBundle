---
layout: home

hero:
  name: DbToolsBundle
  text: Backup, restore and anonymize databases

features:
  - icon:
      light: '/export.svg'
      dark: '/export-d.svg'
    title: Backup
    details: Backup your database and manage your dumps with a simple command.
    link: /backup_restore.html#backup-command
  - icon:
      light: '/import.svg'
      dark: '/import-d.svg'
    title: Restore
    details: Easily restore a previous dump of your database.
    link: /backup_restore.html#restore-command
  - icon:
      light: '/anonymize.svg'
      dark: '/anonymize-d.svg'
    title: Anonymize
    details: Set up database anonymization with a simple YAML configuration file or with PHP attributes.
    link: /anonymization/essentials
  - icon:
      light: '/gdpr.svg'
      dark: '/gdpr-d.svg'
    title: Set up a GDPR-friendly workflow
    details: Make it easier to follow GDPR best practices when importing production dump to other environments.
    link: /anonymization/workflow
  - icon:
      light: '/stats.svg'
      dark: '/stats-d.svg'
    title: Display statistics
    details: Calculate and summarize database statistics.
    link: /stats
  - icon:
      light: '/database.svg'
      dark: '/database-d.svg'
    title: PostgreSQL, MySQL, MariaDB & SQLite ready
    details:
      Work on top of Doctrine DBAL connections with PostgreSQL, MySQL, MariaDB & SQLite.
    link: /getting-started/database-vendors

---

---

<div class="home-grid">
  <div class="home-grid-60">

  <DatabaseCompare/>

  </div>
  <div class="home-grid-40 img">

## Anonymize your database from a simple yaml configuration

```yaml [YAML]
account:
  fisrt_name: firstname
  last_name: lastname
  email_address:
    anonymizer: email
    options: {domain: 'db-tools-bundle.org'}
  hashed_password: password
```
  </div>
</div>


<div class="home-grid">
  <div class="home-grid-40 img">

## Enjoy full integration with Symfony & Laravel

![](/symfony-laravel.svg)

  </div>
  <div class="home-grid-60">

::: code-group
```php [Symfony (Doctrine entity)]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: 'customer')]
class Customer
{
    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'firstname')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'lastname')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'email')]
    private ?string $emailAddress = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'password')]
    private ?string $hashedPassword = null;
}
```
```php [Laravel (Eloquent entity)]

```
:::

  </div>
</div>

<div class="home-grid">
  <div class="home-grid-60">

```yaml
services:
  postgres:
    environment:
      POSTGRES_PASSWORD: password
      POSTGRES_DB: db
      POSTGRES_USER: db
    ports:
      - 5439:5432
    networks:
      - site

  dbtools: // [!code ++]
    image: makinacorpus/dbtoolsbundle:stable // [!code ++]
    networks: // [!code ++]
      - site // [!code ++]
    volumes: // [!code ++]
      - ./db_tools.config.yaml:/var/www/db_tools.config.yaml // [!code ++]

networks:
  site:
```

  </div>
  <div class="home-grid-40 img">

## Deploy an anonymization workflow on any CI/CD with our Docker image

![](/docker.svg)

  </div>
</div>

<MakinaCorpusHorizontal/>

<style>
  .home-grid {
    display: flex;
    flex-wrap: wrap;
    margin-top: 48px;

    h2 {
      padding-top: 0;
      margin-top: 0;
      border: 0;
    }
  }
  .home-grid > div {
    padding: 10px;

    &.img {
      margin-top: auto;
      margin-bottom: auto;
      p {
      border-radius: 12px;
      background: var(--vp-c-bg-soft);
      overflow: hidden;
    }
    }
  }
  .home-grid > div.home-grid-40 {
    width: 40%;
  }
  .home-grid > div.home-grid-60 {
    width: 60%;
  }
</style>