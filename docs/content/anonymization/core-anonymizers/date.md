## Date

Anonymize dates by either:
- randomly choosing an date or datetime in a given range delimited by `min` and `max` options,
- altering the initial value by adding it a random value picked in a range computed from the `delta` options.

`min` and `max` options can be any string that can be parsed as a date by the `DateTime`
class constructor, for example:
 - an absolute date: `2024-03-15` or datetime: `2024-03-15 10:28:56`,
 - a relative time: `now +2 hours`, `-3 month`, ...

`delta` option can be either:
 - an ISO interval specification, such as: `P1DT1M` (1 day and 1 minute),
 - a human readable date string that PHP can parse: `1 month -3 day +3 minutes`.

You can additionnally set the `format` parameter:
- `date` will cast the generated date as a date without time,
- `datetime` will generate a full timestamp.

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            # Will add to the existing date a random interval in the [-delta, +delta] interval.
            birthDate:
                anonymizer: date
                options: {delta: '1 month 15 day'}

        customer:
            # Will pick a random date in the given [min, max] interval.
            lastLogin:
                anonymizer: date
                options: {min: 'now -3 month', max: 'now'}

        customer:
            # And example with absolute dates.
            createdAt:
                anonymizer: date
                options: {min: '1789-05-05', max: '2024-03-15', format: 'date'}
  #...
```

@@@
@@@ symfony

::: code-group
```php [Attribute]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: 'customer')]
class Customer
{
    // ...

    #[ORM\Column]
    // Will add to the existing date a random interval // [!code ++]
    // in the [-delta, +delta] interval. // [!code ++]
    #[Anonymize(type: 'date', options: ['delta' => '1 month 15 day'])] // [!code ++]
    private ?\DateTime $birthDate = null;

    #[ORM\Column]
    // Will pick a random date in the given // [!code ++]
    // [min, max] interval // [!code ++]
    #[Anonymize(type: 'date', options: ['min' => 'now -3 month', 'max' => 'now'])] // [!code ++]
    private ?\DateTimeImmutable $lastLogin = null;

    #[ORM\Column]
    // And example with absolute dates. // [!code ++]
    #[Anonymize(type: 'date', options: ['min' => '1789-05-05', 'max' => '2024-03-15', 'format' => 'date'])] // [!code ++]
    private ?\DateTime $createdAt = null;
}
```

```yml [YAML]
# config/anonymization.yaml

customer:
    # Will add to the existing date a random interval in the [-delta, +delta] interval.
    birthDate:
        anonymizer: date
        options: {delta: '1 month 15 day'}

customer:
    # Will pick a random date in the given [min, max] interval.
    lastLogin:
        anonymizer: date
        options: {min: 'now -3 month', max: 'now'}

customer:
    # And example with absolute dates.
    createdAt:
        anonymizer: date
        options: {min: '1789-05-05', max: '2024-03-15', format: 'date'}

#...
```
:::

@@@

:::warning
Dates you give for `min` and `max` values will inherit from the PHP default
configured timezone.
:::

:::info
When using a date range over 68 years, random granularity stops at the hour
in order to avoid date add operation to be given an overflowing int value.
:::
