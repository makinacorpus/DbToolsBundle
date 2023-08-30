# DbToolsBundle

Work in progress !

A set of Symfony Commands to manipulate databases :

- `db-tools:backup` : backup your database and deals with old backups cleanup
- `db-tools:restore` : Restore your database from previous backups
- `db-tools:anonymize` : Launch setted up anonymization on your database

Currently supported database vendors : PostgreSQL, MariaDB/MySQL

Roadmap :

- [ ] Add tests and set up a CI
- [ ] Backup encryption
- [ ] New anonymizers
  - [ ] Common
    - [ ] Address
    - [ ] Iban
    - [ ] Bic
    - [ ] Phone number
  - [ ] FrFR
    - [ ] Numéro de sécurité sociale
    - [ ] Numéro de téléphone (Implementation of `Common\PhoneNumberAnonymzer`)
    - [ ] Adresse (Implementation of `Common\AddressAnonymzer`)
