# Changelog

## 1.1.0

* [feature] Anonymizers - Add options validation method (#97, #128, #131, #133)
* [internal] Backupper, Restorer, Anonymizator - Change the way we output information during processes (#103)
* [feature] ⭐️ FloatAnonymizer - Add possibility to anonymize value by adding noise (#86, #113)
* [feature] ⭐️ Add ConstantAnonymizer (#115, #119)
* [feature] ⭐️ Add NullAnonymizer (#114, #116)
* [feature] LoremIpsumAnonymizer - Add some customization options (#90, #112)
* [feature] ⭐️ IntegerAnonymizer - Add possibility to anonymize value by adding noise (#84, #110)
* [internal] Backup and Restore Commands - Some internal code base refactors (#100, #104)
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