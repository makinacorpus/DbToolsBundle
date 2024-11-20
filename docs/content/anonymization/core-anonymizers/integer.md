## IntegerAnonymizer

Anonymize integers by:
- randomly choosing an integer in a range delimited by 'min' and 'max' options
- altering the initial value by adding it a random value picked in a range computed from the 'delta' or 'percent' options

<div class=standalone>

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            age:
                anonymizer: integer
                options: {min: 10, max: 99}

        customer:
            # Will add to each age value a random integer
            # in the [-delta, +delta] interval
            # In this example, an integer between -15 and 15 will be
            # added to the initial value
            age:
                anonymizer: integer
                options: {delta: 15}

        customer:
            # Will add to each age value a random percent
            # of the initial value in the [-percent%, +percent%] interval
            # In this example, a value between -10% and 10% of the initial value
            # will be added to age.
            age:
                anonymizer: integer
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
    // Will fill the age column with a random integer // [!code ++]
    // in the [min, max] interval // [!code ++]
    #[Anonymize(type: 'integer', options: ['min' => 10, 'max' => 99])] // [!code ++]
    private ?int $age = null;

    #[ORM\Column]
    // Will add to each age value a random integer // [!code ++]
    // in the [-delta, +delta] interval // [!code ++]
    // In this example, an integer between -15 and 15 will be // [!code ++]
    // added to the initial value // [!code ++]
    #[Anonymize(type: 'integer', options: ['delta' => 15])] // [!code ++]
    private ?int $age = null;

    #[ORM\Column]
    // Will add to each age value a random percent // [!code ++]
    // of the initial value in the [-percent%, +percent%] interval // [!code ++]
    // In this example, a value between -10% and 10% of the initial value // [!code ++]
    // will be added to age. // [!code ++]
    #[Anonymize(type: 'integer', options: ['percent' => 10])] // [!code ++]
    private ?int $age = null;
    // ...
}
```

```yml [YAML]
# config/anonymization.yaml

customer:
    age:
        anonymizer: integer
        options: {min: 10, max: 99}

customer:
    # Will add to each age value a random integer
    # in the [-delta, +delta] interval
    # In this example, an integer between -15 and 15 will be
    # added to the initial value
    age:
        anonymizer: integer
        options: {delta: 15}

customer:
    # Will add to each age value a random percent
    # of the initial value in the [-percent%, +percent%] interval
    # In this example, a value between -10% and 10% of the initial value
    # will be added to age.
    age:
        anonymizer: integer
        options: {percent: 10}

#...
```
:::

</div>
