## NullAnonymizer

Set all values to `NULL`.

<div class=standalone>

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            sensible_content: 'null'
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
    #[Anonymize(type: 'null')] // [!code ++]
    private ?string $sensibleContent = null;

    // ...
}
```

```yml [YAML]
# config/anonymization.yaml

customer:
    sensible_content: 'null'

#...
```
:::

</div>
