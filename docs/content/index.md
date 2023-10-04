---
layout: home

hero:
  name: DbToolsBundle
  text: a set of Symfony Console Commands to interact with your database
  tagline: by Makina Corpus
  image:
    src: ./images/logo.svg
    alt: DbToolsBundle by Makina Corpus
  actions:
    - theme: brand
      text: Get Started
      link: /introduction/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/vuejs/vitepress

features:
  - icon:
      src: '/images/export.svg'
    title: Backup
    details: Backup your database and manage your dumps with a simple command.
  - icon:
      src: '/images/import.svg'
    title: Restore
    details: Easily restore a previous dump of your database.
  - icon:
      src: '/images/anonymize.svg'
    title: Anonymize
    details: Configure database anonymization with a single yaml using provided or custom anonymizers.
  - icon:
      src: '/images/gdpr.svg'
    title: Set up a GRDP-friendly workflow
    details: Make it easier to follow GDPR best practices when importing production dump to other environments.
  - icon:
      src: '/images/stats.svg'
    title: Display stats
    details: Calculate and summarize database statistics.
  - icon:
      src: '/images/database.svg'
    title: PostgreSQL & MariaDB/MySQL ready
    details: Work on top of Doctrine DBAL connections with PostgreSQL & MariaDB/MySQL.

---