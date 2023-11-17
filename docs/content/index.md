---
layout: home

hero:
  name: DbToolsBundle
  text: a set of Symfony Console Commands to interact with your database
  image:
    src: ./logo.svg
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
      light: '/export.svg'
      dark: '/export-d.svg'
    title: Backup
    details: Backup your database and manage your dumps with a simple command.
  - icon:
      light: '/import.svg'
      dark: '/import-d.svg'
    title: Restore
    details: Easily restore a previous dump of your database.
  - icon:
      light: '/anonymize.svg'
      dark: '/anonymize-d.svg'
    title: Anonymize
    details: Set up database anonymization with attributes on Doctrine Entities or with a YAML configuration file.
  - icon:
      light: '/gdpr.svg'
      dark: '/gdpr-d.svg'
    title: Set up a GRDP-friendly workflow
    details: Make it easier to follow GDPR best practices when importing production dump to other environments.
  - icon:
      light: '/stats.svg'
      dark: '/stats-d.svg'
    title: Display stats
    details: Calculate and summarize database statistics.
  - icon:
      light: '/database.svg'
      dark: '/database-d.svg'
    title: PostgreSQL & MariaDB/MySQL ready
    details: Work on top of Doctrine DBAL connections with PostgreSQL & MariaDB/MySQL.

---