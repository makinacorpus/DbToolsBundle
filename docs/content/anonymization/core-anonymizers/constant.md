## ConstantAnonymizer

Set all value to a constant value.
Options are:
* `value`: the value you want to use to fill the column
* `type`: a SQL type for the given value
  (default value is `text`)

<div class=standalone>

```yaml [YAML]
# db_tools.anonymization.yaml
anonymization:
    tables:
        customer:
            address:
                sensible_content:
                    type: constant
                    options: {value: '_______'}
        # or for example
        customer:
            address:
                sensible_content:
                    type: constant
                    options: {value: '2012-12-21', type: 'date'}
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
    #[Anonymize(type: 'constant', options: ['value' => '_______'])] // [!code ++]
    private ?string $sensibleContent = null;

    #[ORM\Column]
    #[Anonymize(type: 'constant', options: ['value' => '2012-12-21', 'type' => 'date'])] // [!code ++]
    private ?string $sensibleContent = null;

    // ...
}
```

```yml [YAML]
# config/anonymization.yaml

customer:
    sensible_content:
        type: constant
        options: {value: '_______'}

customer:
    sensible_content:
        type: constant
        options: {value: '2012-12-21', type: 'date'}

#...
```
:::

</div>
