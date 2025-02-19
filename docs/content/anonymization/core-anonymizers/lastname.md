## LastnameAnonymizer

Works like the StringAnonymizer, but with a provided sample of 1000 worldwide lastnames.

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            lastname: lastname
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
    #[Anonymize('lastname')] // [!code ++]
    private ?string $lastname = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    lastname: lastname
#...
```
:::

@@@
