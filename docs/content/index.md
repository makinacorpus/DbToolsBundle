---
layout: home

hero:
  name: DbToolsBundle
  text: Backup, restore and anonymize your data
  image:
    light: ./logo.svg
    dark: ./logo-d.svg
    alt: DbToolsBundle by Makina Corpus
  actions:
    - theme: brand
      text: Get Started
      link: ./getting-started/introduction
    - theme: alt
      text: View on GitHub
      link: https://github.com/makinacorpus/DbToolsBundle
    # - theme: alt
    #   text: View on Packagist
    #   link: https://github.com/makinacorpus/DbToolsBundle

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
    details: Set up database anonymization with PHP attributes on Doctrine Entities or with a YAML configuration file.
    link: /anonymization/essentials
  - icon:
      light: '/gdpr.svg'
      dark: '/gdpr-d.svg'
    title: Set up a GDPR-friendly workflow
    details: Make it easier to follow GDPR best practices when importing production dump to other environments.
    link: /anonymization/command.html#a-gdpr-friendly-workflow
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
