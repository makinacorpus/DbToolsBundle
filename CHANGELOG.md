# Changelog

## Next

* [feature] ⭐️ Add `bin/db-tools` CLI command allowing standalone usage (#153).
* [experimental] ⭐️ Add `bin/compile` CLI command for building a PHAR file (#154).
* [bc] Password anonymizer `symfony/password-hasher` dependency is now optional and must be manually installed (#155).
* [internal] All Doctrine related dependencies are now optional (#155).
* [internal] Move Symfony related code into the `src/Bridge/Symfony` folder and associated namespace (#155).
* [internal] More efficient anonymizer pack lookup (#165).

## 1.2.1

* [fix] Anonymization - Sample table creation fails if sample is too big with sqlsrv (#174)

## 1.2.0

* [feature] ⭐️ Add Doctrine DBAL 4.0 compatibility (#140).
* [feature] ⭐️ Add Doctrine ORM 3.0 compatibility as a side effect of Doctrine DBAL 4.0 support (#140).
* [feature] ⭐️ Anonymization - Add Doctrine Embeddables support (#105).
* [feature] ⭐️ Anonymization - Add Doctrine entity joined inheritance support (#160)
* [feature] ⭐️ Anonymization - Finalized and improved IBAN/BIC anonymizer (#4)
* [fix] Restored MySQL 5.7 support (#124)
* [internal] Remove `doctrine/dbal` dependency from all code except the database session registry (#142).
* [internal] Introduce `DatabaseSessionRegistry` as single entry point for plugging-in database (#142).
* [internal] Use `makinacorpus/query-builder` schema manager for DDL alteration (#140).
* [internal] Raise `makinacorpus/query-builder` dependency to version 1.5.5 (#140).
* [internal] Many improvements in local/CI `./dev.sh` test script.

## 1.1.0

* [feature] ⭐️ Add DateAnonymizer (#32)
* [feature] Anonymizers - Add options validation method (#97, #128, #131, #133)
* [internal] Backupper, Restorer, Anonymizator - Change the way we output information during processes (#103)
* [feature] ⭐️ FloatAnonymizer - Add possibility to anonymize value by adding noise (#86, #113)
* [feature] ⭐️ Add ConstantAnonymizer (#115, #119)
* [feature] ⭐️ Add NullAnonymizer (#114, #116)
* [feature] LoremIpsumAnonymizer - Add some customization options (#90, #112)
* [feature] ⭐️ IntegerAnonymizer - Add possibility to anonymize value by adding noise (#84, #110)
* [internal] Backup and Restore Commands - Some internal code base refactorings (#100, #104)
* [feature] Backup and Restore Commands - Make usage and management of default and extra options more convenient (#79, #99)
* [feature] ⭐️ Backup and Restore Commands - Allow to provide custom options for backup and restoration tasks (#79, #94)
* [feature] Anonymize Command - Make output more compact in none-verbose mode (#92, #93)
* [feature] ⭐️ Storage - Add filename strategy customization (#81)

## 1.0.6

* [fix] Some minor fixes in anonymizers (#108)

## 1.0.5

* [fix] Anonymization - AttributeLoader - Temporary fix in order to ignore Doctrine Embeddable (#107)

## 1.0.4

* [feature] Add salt to md5 and email anonymizers (#95, #96)

## 1.0.3

* [fix] MySQL Anonymization process - Fix join id index creation (#89)

## 1.0.2

* [fix] SQLite Restorer - Reset the Doctrine connection after restoration (#87)

## 1.0.1

* [fix] Anonymization - AttributeLoader - Ignore MappedSuperclass entities (#83)
* [fix] Anonymization Command - Fix wrong condition on cancel confirmation (#84)

## 1.0.0

Initial release.
