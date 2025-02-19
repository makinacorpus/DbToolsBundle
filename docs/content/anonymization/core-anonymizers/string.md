## StringAnonymizer

This *Anonymizer* will fill configured column with a random value from a given sample.

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            level:
                anonymizer: string
                options: {sample: ['none', 'bad', 'good', 'expert']}
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

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'string', options: ['sample' => ['none', 'bad', 'good', 'expert']])] // [!code ++]
    private ?string $level = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml
customer:
    level:
        anonymizer: string
        options: {sample: ['none', 'bad', 'good', 'expert']}
#...
```
:::

@@@

:::tip
If you use the same sample multiple times, if you use a large sample or if you use a generated one, it could be
more efficient and convinient to create your own custom anonymizer, see the [Custom Anonymizers](/anonymization/custom-anonymizers)
section to learn how to do that.
:::
