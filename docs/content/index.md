---
layout: home

hero:
  name: DbToolsBundle
  text: Back up, restore and anonymize databases

features:
  - icon:
      light: '/export.svg'
      dark: '/export-d.svg'
    title: Back up
    details: Back up your database and manage your dumps with a simple command.
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

## Anonymize your database from a simple YAML configuration

Map each column of each table you want to anonymize with
a specific anonymizer.

```yaml [YAML]
account:
  fisrt_name: firstname
  last_name: lastname
  email_address:
    anonymizer: email
    options: {domain: 'db-tools-bundle.org'}
  hashed_password: password
```

[Learn more about anonymization](./anonymization/essentials)

  </div>
</div>


<div class="home-grid">
  <div class="home-grid-40 img">

## Enjoy full integration with Symfony & Laravel

*DbToolsBundle* provides a bundle for Symfony and an
experimental package for Laravel. These integrations include
autoconfiguration of database connection.

![](/symfony-laravel.svg)


[Learn more about Symfony integration](./getting-started/flavors#symfony)
[Learn more about Laravel integration](./getting-started/flavors#laravel)

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
```php [Laravel]
<?php
// config/db-tools.php

declare(strict_types=1);

return [
  // ...
  'anonymization' => [
    'first_name' => [
      'anonymizer' => 'firstname'
    ]
    'last_name' => [
      'anonymizer' => 'lastname'
    ]
    'email_address' => [
      'anonymizer' => 'email'
    ]
    'hashed_password' => [
      'anonymizer' => 'password'
    ]
  ],
];
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

The Docker image unlocks the *DbToolsBundle* features for every DevOps teams.
Simply add our image to your `docker-compose.yaml`!

![](/docker.svg)

[Learn more about Docker image](./getting-started/flavors#docker)

  </div>
</div>

<MakinaCorpusHorizontal/>

<style>
  hr + .home-grid {
    margin-top: 80px;
  }
  .home-grid {
    display: flex;
    flex-wrap: wrap;
    margin-top: 48px;

    h2 {
      padding-top: 0;
      margin-top: 0;
      border: 0;
      color: var(--vp-c-brand-2);
      font-weight: 700;
      font-size: 35px;
    }
  }
  .home-grid > div {
    margin-top: auto;
    margin-bottom: auto;
    &.img {

      img {
        border-radius: 12px;
        background: var(--vp-c-bg-soft);
        overflow: hidden;
        height: 200px;
        margin-left: auto;
        margin-right: auto;
      }

      a {
        display: block;
        margin: 10px 0;
        text-decoration: none;
        text-align: center;
        border-color: var(--vp-button-alt-border);
        color: var(--vp-button-alt-text);
        background-color: var(--vp-button-alt-bg);
        border-radius: 20px;
        padding: 0 20px;
        line-height: 38px;
        font-size: 14px;transition: color 0.25s, border-color 0.25s, background-color 0.25s;

        &:hover {
          border-color: var(--vp-button-alt-hover-border);
          color: var(--vp-button-alt-hover-text);
          background-color: var(--vp-button-alt-hover-bg);
        }
      }
    }
  }
  .home-grid > div.home-grid-60 {
    width: 100%;
    order: 1;
  }
  .home-grid > div.home-grid-40 {
    width: 100%;
  }
  @media (min-width: 960px) {
    .home-grid > div.home-grid-40 {
      width: 40%;
    }
    .home-grid > div.home-grid-60 {
      width: 60%;
      order: unset;
    }
    .home-grid > div:first-child {
      padding-right: 30px;
    }
    .home-grid > div:last-child {
      padding-left: 30px;
    }
    .home-grid > div.img img {
      height: auto;
    }
  }
</style>