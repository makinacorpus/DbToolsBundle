## FloatAnonymizer

Anonymize float by:
- randomly choosing an integer in a range delimited by `min` and `max` options
- altering the initial value by adding it a random value picked in a range computed from the `delta` or `percent` options

You may also specify a `precision` (default 2).

<div class=standalone>

```yaml [YAML]
# db_tools.anonymization.yaml
anonymization:
    tables:
        customer:
            # Will fill the size column with a random float
            # in the [min, max] interval.
            size:
                anonymizer: float
                options: {min: 120, max: 300, precision: 4}

        customer:
            # Will add to each size value a random integer
            # in the [-delta, +delta] interval.
            # In this example, an integer between -15.5 and 15.5 will be
            # added to the initial value.
            size:
                anonymizer: float
                options: {delta: 15.5}

        customer:
            # Will add to each size value a random percent
            # of the initial value in the [-percent%, +percent%] interval.
            # In this example, a value between -10% and 10% of the initial value
            # will be added to the initial value.
            size:
                anonymizer: float
                options: {percent: 10}
  #...
```

</div>
<div class="symfony">

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
    // Will fill the size column with a random float // [!code ++]
    // in the [min, max] interval. // [!code ++]
    #[Anonymize(type: 'float', options: ['min' => 10, 'max' => 99, 'precision' => 4])] // [!code ++]
    private ?float $size = null;

    #[ORM\Column]
    // Will add to each size value a random integer // [!code ++]
    // in the [-delta, +delta] interval. // [!code ++]
    // In this example, an integer between -15.5 and 15.5 will be // [!code ++]
    // added to the initial value. // [!code ++]
    #[Anonymize(type: 'float', options: ['delta' => 15.5, 'precision' => 4])] // [!code ++]
    private ?float $size = null;

    #[ORM\Column]
    // Will add to each size value a random percent // [!code ++]
    // of the initial value in the [-percent%, +percent%] interval. // [!code ++]
    // In this example, a value between -10% and 10% of the initial value // [!code ++]
    // will be added to the initial value. // [!code ++]
    #[Anonymize(type: 'float', options: ['percent' => 10])] // [!code ++]
    private ?float $size = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    # Will fill the size column with a random float
    # in the [min, max] interval.
    size:
        anonymizer: float
        options: {min: 120, max: 300, precision: 4}

customer:
    # Will add to each size value a random integer
    # in the [-delta, +delta] interval.
    # In this example, an integer between -15.5 and 15.5 will be
    # added to the initial value.
    size:
        anonymizer: float
        options: {delta: 15.5}

customer:
    # Will add to each size value a random percent
    # of the initial value in the [-percent%, +percent%] interval.
    # In this example, a value between -10% and 10% of the initial value
    # will be added to the initial value.
    size:
        anonymizer: float
        options: {percent: 10}
#...
```
:::

</div>
