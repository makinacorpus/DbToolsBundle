# Supported database vendors

Fully supported database vendors are:

- PostgreSQL 10 and above
  <br><small>(previous versions from 9.5 are untested but should work)</small>
- MariaDB 10.11 and above
- MySQL 8.0 and above

Partially supported database vendors are:

- SQlite 3.0 and above
- SQL Server 2019 and above
  <br><small>(previous versions from 2015 are untested but should work)</small>

Some of those vendors are only unit-tested and have never been really experienced in production.
If you use one of them in production and think it should/could be marked as "Working",
feel free to [open an issue on GitHub](https://github.com/makinacorpus/DbToolsBundle/issues?q=is%3Aopen+is%3Aissue+label%3A%22Database+vendor+support%22).

If the vendor you use is unsupported and you want it to be added to this list,
feel free to [open an issue on GitHub](https://github.com/makinacorpus/DbToolsBundle/issues?q=is%3Aopen+is%3Aissue+label%3A%22Database+vendor+support%22).
If there is enough people interested, we could consider to add it.

Here is a matrix of the current state of support:

| Vendor                     | Backup/Restore  | Anonymization | Stats       |
|----------------------------|----------------|---------------|-------------|
| PostgreSQL    |  <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> |
| MySQL    |  <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> |
| MariaDB    |  <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> | <Badge type="tip" text="✔" title="Working"/> |
| SQLite    |  <Badge type="tip" text="✔" title="Working"/> | <Badge type="warning" text="~" title="Only unit-tested"/> | <Badge type="danger" text="✘" title="Unsupported"/> |
| SQL Server    |  <Badge type="danger" text="✘" title="Unsupported"/> (<a target="_blank" href="https://github.com/makinacorpus/DbToolsBundle/issues/61">see issue</a>) | <Badge type="warning" text="~" title="Only unit-tested"/> | <Badge type="danger" text="✘" title="Unsupported"/> |

<Badge type="tip" text="✔" /> Working - <Badge type="warning" text="~" /> Only unit-tested - <Badge type="danger" text="✘" /> Unsupported
