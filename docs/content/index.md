---
layout: home

hero:
  name: DbToolsBundle
  text: a set of Symfony Console Commands to interact with your database
  image:
    src: ./images/logo.svg
    alt: DbToolsBundle by Makina Corpus
  actions:
    - theme: brand
      text: Get Started
      link: ./introduction
    - theme: alt
      text: View on GitHub
      link: https://github.com/vuejs/vitepress
    - theme: alt
      text: View on Packagist
      link: https://github.com/vuejs/vitepress

features:
  - icon:
      light: '/images/export.svg'
      dark: '/images/export-d.svg'
    title: Backup
    details: Backup your database and manage your dumps with a simple command.
  - icon:
      light: '/images/import.svg'
      dark: '/images/import-d.svg'
    title: Restore
    details: Easily restore a previous dump of your database.
  - icon:
      light: '/images/anonymize.svg'
      dark: '/images/anonymize-d.svg'
    title: Anonymize
    details: Set up database anonymization with attributes on Doctrine Entities or with a YAML configuration file.
  - icon:
      light: '/images/gdpr.svg'
      dark: '/images/gdpr-d.svg'
    title: Set up a GRDP-friendly workflow
    details: Make it easier to follow GDPR best practices when importing production dump to other environments.
  - icon:
      light: '/images/stats.svg'
      dark: '/images/stats-d.svg'
    title: Display stats
    details: Calculate and summarize database statistics.
  - icon:
      light: '/images/database.svg'
      dark: '/images/database-d.svg'
    title: PostgreSQL & MariaDB/MySQL ready
    details: Work on top of Doctrine DBAL connections with PostgreSQL & MariaDB/MySQL.

---