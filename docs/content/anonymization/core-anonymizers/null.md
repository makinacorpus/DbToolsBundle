## NullAnonymizer

Set all values to `NULL`.

@@@ standalone docker
```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            sensible_content: 'null'
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
@@@
