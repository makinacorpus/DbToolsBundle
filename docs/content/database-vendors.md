# Database vendors support

Officially, supported database vendors are:

- PostgreSQL 10 and above
- MySQL 5.7 and above

Partially supported database vendors are:

- MariaDB 10.11 and above
- SQlite 3.0 and above
- SQL Server 2019 and above

But, some of the DbToolsBundle functionnalities could work with other vendors.

Some of those vendors are only unit-tested and have never been really experienced in production. If you use one of
them in production and think it should/could be marked as "Working", feel free to [open an issue on Github](https://github.com/makinacorpus/DbToolsBundle/issues/new).

If the vendor you use is unsupported and you want it to be added to this list, feel free to [open an issue on Github](https://github.com/makinacorpus/DbToolsBundle/issues/new).
If there is enough people interested, we could consider to add it.

Here is a matrix of the current state of support:


| Vendor                     | Backup/Restore  | Anonymization | Stats       |
|----------------------------|----------------|---------------|-------------|
| PostgreSQL 10 or higher    |  <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> |
| MySQL 5.7 or higher    |  <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> |
| MariaDB 10.11 or higher    |  <Badge type="danger" text="✘" title="Unsupported"/> | <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> |
| SQLite    |  <Badge type="danger" text="✘" title="Unsupported"/> | <Badge type="warning" text="~" title="Only unit-tested"/> | <Badge type="danger" text="✘" title="Unsupported"/> |
| SQL Server    |  <Badge type="danger" text="✘" title="Unsupported"/> | <Badge type="warning" text="~" title="Only unit-tested"/> | <Badge type="danger" text="✘" title="Unsupported"/> |

<Badge type="tip" text="✔" /> Working - <Badge type="warning" text="~" /> Only unit-tested - <Badge type="danger" text="✘" /> Unsupported
