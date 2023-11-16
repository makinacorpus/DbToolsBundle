# Internals

## Basics

Anonymization processus is based upon a single `UPDATE` SQL query, each anonymizer
adds it own `SET` statements, and a few `JOIN` clauses if necessary.

Enum and multiple-column anonymizers are base implementations for anonymizers
that use pre-generated sample data lists as source values for anonymizing
your database. In order to make those efficient, the sample data lists are
first inserted into a temporary table in database.

Once data is inserted into temporary tables, anonymization will use an SQL
`JOIN` statement on the anonymizing SQL `UPDATE` query.

Joining on the target table to anonymize is only possible if you have an arbitrary
row identifier to `JOIN` on. Without an identifier, RDBMS will optimise
data-unrelated joins to a single selected row from the sample table, and all
target table rows will have the same value.

In order to achieve this, the anonymizator will add a **temporary** integer
column on each target table and populate it using a sequence, that will be
dropped once anonymization is done.

Some anonymizers will generate their own `SET` statements instead of using
a sample table join, they are not covered by this documentation page.

## Targeted UPDATE query

### Standard SQL

Considering that:

 - `"client"` is the target table the user wants to be anonymized,
 - `"sample_1"` is a sample table, corresponding to a first anonymizer,
 - `"sample_2"` is another sample table, corresponding to a second anonymizer,

the nearest SQL standard variant we target, which actually is for PostgreSQL is:

```sql
UPDATE
    "client"
SET
    "nom" = "sample_1"."value",
    "civilite" = "sample_2"."value"

-- See note 1: self-join using FROM.
FROM "client" AS "_target_table"

LEFT JOIN (
    SELECT
        "value",
        -- See note 2: where the random happens.
        ROW_NUMBER() OVER (ORDER BY random()) AS "rownum"
    FROM "sample_1"
    -- See note 4: minor micro-optimisation.
    LIMIT 171224
) AS "sample_1"
    -- See note 1: we join over the FROM table, not the target one.
    -- See note 3: MOD() function usage for joining.    
    ON MOD("_target_table"."_db_tools_id", 589) = "sample_1"."rownum"

LEFT JOIN (
    SELECT
        "value",
        -- See note 2: where the random happens.
        ROW_NUMBER() OVER (ORDER BY random()) AS "rownum"
    FROM "sample_2"
    -- See note 4: minor micro-optimisation.
    LIMIT 171224
) AS "sample_2"
    -- See note 1: we join over the FROM table, not the target one.
    -- See note 3: MOD() function usage for joining.
    ON MOD("_target_table"."_db_tools_id", 2) = "sample_2"."rownum"

WHERE
    -- See note 1: self-join WHERE condition
    "client"."_target_table" =  "_target_table"."_db_tools_id" -- Pour le self-join
;
```

1. SQL Standard `UPDATE` query cannot `JOIN` over the updated table, we only
   can use a cross join using the `FROM` statement. Other `JOIN` statements
   can only target tables from the `FROM` clause.

   In order to work around this, we do a cartesian production of the target
   table with itself, using the anonymizer identifier as `JOIN` condition in
   the `WHERE` clause.

2. In order to randomly select samples from the sample table, we add an
   `ORDER BY random()` clause. `random()` expression will vary depending upon
   the RDBMS implementation.

   Because we need to `JOIN` over the target table, otherwise most RDBMS would
   use a single row and apply it to all update row as an optimization, we
   select the `ROW_NUMBER()` over the ordered by random partition. We will use
   this row number to `JOIN` on the anonymizer identifier.

3. Because the sample table in most case will have less rows than the target
   table, we need to reduce the anonymizer identifier to the sample table row
   count in order for each target row to have a corresponding sample value.
   This is done using the `MOD()` (modulo) magic in the `JOIN` condition.

4. When the target table has less rows than the sample table, we restrict
   each sample table `JOIN` `LIMIT` statement to the target table count. Table
   is counted prior to anonymization.

### MySQL specifics

MySQL has many limitations:

 - it has a very particuliar way of joining in `UPDATE` queries allowing to
   `JOIN` over the updated table directly,

 - it does not optimize joining over a self table if the joined column has
   no index, it does not detect the redundancy and will execute a cartesian
   product of the target table over the target table, which explodes the
   complexity to a point where a single second SQL query on PostgreSQL will
   literally take hours to execute on MySQL.

In order to workaround this, we chose to create an index over the anonymizer
identifier sequence, which wouldn't be necessary otherwise.

### Other variants

Only PostgreSQL and MySQL are extensively tested for now, other SQL dialects
will be supported in the future.
