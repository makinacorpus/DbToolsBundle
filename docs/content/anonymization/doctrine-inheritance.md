# Doctrine ORM and entity inheritance

Doctrine ORM allows various complex inheritance scenarios, support of those scenarios
is usable in some case, but partial or non working in others.

## Embeddables

Embeddables are not quite inheritance, but composition instead. This use case is
fully supported since all properties from the nested entity live in the entity
that uses it table.

When the anonymizators builds the SQL query for anonymization, all columns are
in the same table and run all at once.

## Joined inheritance

Joined inheritance has a very basic testing scenario, and should work flawlessly
in most cases. When doing joined inheritance, the parent entity table exists
separatly from the concrete entity implementation.

Anonymizator will hence build up two different SQL queries for anonymizing and
live with it, one for the child class, and the other for the parent class.

Yes, it has a few drawbacks explained below.

:::warning
**You cannot use a multi-column anonymizator on columns from the parent entity and columns of the child entity at the same time.**

By design, this API prevents this, and there will never be any work around.
:::

:::warning
**When anonymizing parent entity columns, the SQL query will not restrict to the a certain child type.**

This means that all entities in the discriminator map will be anonymized at once,
none will be filtered out.
:::

## Other inheritance types

Other inheritances types have not be tested yet, but may work.

:::tip
The underlaying code handling inheritance in the anonymizator attribute lookup
is generic enough so other inheritance types may actually work gracefully and
transparently.

Testing it into your project is easy, [reporting an issue](https://github.com/makinacorpus/DbToolsBundle/issues)
is as well! We are here to help you whenever a problem arise.
:::
