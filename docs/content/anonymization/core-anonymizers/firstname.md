## FirstnameAnonymizer

Works like the StringAnonymizer, but with a provided sample of 1000 worldwide firstnames.

<div class=standalone>

```yaml [YAML]
# db_tools.anonymization.yaml
anonymization:
    tables:
        customer:
            firstname: firstname
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

</div>
