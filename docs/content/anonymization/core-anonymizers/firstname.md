## Firstname

Works like the StringAnonymizer, but with a provided sample of 1000 worldwide firstnames.

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            firstname: firstname
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
    #[Anonymize('firstname')] // [!code ++]
    private ?string $firstname = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    firstname: firstname
#...
```
:::

@@@
