# Custom Anonymizers

## Basics

The *DbToolsBundle* allows you to create your own *Anonymizers*.

To create one, you will need to

1. add a class in the `src/Anonymizer` directory extends `AbstractAnonymizer`
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
```

To understand how an `Anonymizer` works, read `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/AbstractAnonymizer.php`
which is self-documented.

To inspire you, browse existing *Anonymizers* in:

* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/Core`
* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/FrFR`

::: tip
You can tell the *DbToolsBundle* your *Custom Anonymizers* live in a different directory
with the [*Anonymizer paths* configuration](../configuration#anonymizer-paths).
:::

::: tip
To generate its update queries, the *DbToolsBundle* use the ***php-query-builder***.
If you want to create your own anonymizers, you will problably need to take a look at
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

    /**
     * {@inheritdoc}
     */
    protected function getSample(): array
    {
        // Generate here your sample.

        return ['Foo', 'Bar', 'Baz'];
    }
}
```

## Mutlicolumn Anonymizers

As for an enum anonymizer, if you need to create a mutlicolumn anonymizer based on a big sample, you can extend the
*AbstractMultipleColumnAnonymizer*.

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
    /**
     * @inheritdoc
     */
    protected function getColumnNames(): array
    {
        // Declare here name fo each part of your multicolumn
        // anonymizer
        return [
            'part_one',
            'part_two',
            'part_three',
        ];
    }

    /**
     * {@inheritdoc}
     */
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