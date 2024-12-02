# Custom Anonymizers

## Basics

*DbToolsBundle* allows you to create your own *Anonymizers*.

To create one, you will need to

1. add a class extending `AbstractAnonymizer` in the `src/Anonymizer` directory
2. add the `AsAnonymizer` attribute on it

```php
namespace App\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'my_anonymizer', // a snake case string
    pack: 'my_app', // a snake case string
    description: <<<TXT
    Describe here if you want how your anonymizer works.
    TXT
)]
class MyAnonymizer extends AbstractAnonymizer
{
    // ...
}
```

To understand how an `Anonymizer` works, read `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/AbstractAnonymizer.php`
which is self-documented.

To inspire you, browse existing *Anonymizers* in `vendor/makinacorpus/db-tools-bundle/src/Anonymization/Anonymizer/Core`.

::: tip
You can tell *DbToolsBundle* your *Custom Anonymizers* live in a different directory
with the [*Anonymizer paths* configuration](../configuration/basics#anonymizer-paths).
:::

::: tip
To generate its update queries, *DbToolsBundle* uses the *[makinacorpus/query-builder-bundle](https://github.com/makinacorpus/query-builder-bundle) package*.
If you want to create your own anonymizers, you will probably need to take a look at
[its basic uses](https://php-query-builder.readthedocs.io/en/stable/introduction/usage.html).
:::

## Enum Anonymizers

A classic need is to anonymize a column filling it with a random value from a large sample.

For example, it's what is done by the *FirstNameAnonymizer* and the *LastNameAnonymizer* which use
a sample of 1000 items.

If you need to create such a anonymizer, extend the *AbstractEnumAnonymizer*.

Here is a complete example:

```php
namespace App\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'my_enum_anonymizer',
    pack: 'my_app',
    description: <<<TXT
    Describe here if you want how your anonymizer works.
    TXT
)]
class MyEnumAnonymizer extends AbstractEnumAnonymizer
{
    #[\Override]
    protected function getSample(): array
    {
        // Generate here your sample.
        return ['Foo', 'Bar', 'Baz'];
    }
}
```

## Multicolumn Anonymizers

As for an enum anonymizer, if you need to create a multicolumn anonymizer based
on a big sample, you can extend the *AbstractMultipleColumnAnonymizer*.

Here is a complete example:

```php
namespace App\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractMultipleColumnAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'my_multicolumn_anonymizer',
    pack: 'my_app',
    description: <<<TXT
    Describe here if you want how your anonymizer works.
    TXT
)]
class MyMulticolumnAnonymizer extends AbstractMultipleColumnAnonymizer
{
    #[\Override]
    protected function getColumnNames(): array
    {
        // Declare here name of each part of your multicolumn anonymizer.
        return [
            'part_one',
            'part_two',
            'part_three',
        ];
    }

    #[\Override]
    protected function getSample(): array
    {
        // Generate here your sample.
        return [
            ['Foo', 'Bar', 'Baz'],
            ['Foo1', 'Bar1', 'Baz1'],
            ['Foo2', 'Bar2', 'Baz2'],
            ['Foo3', 'Bar3', 'Baz3'],
        ];
    }
}
```
