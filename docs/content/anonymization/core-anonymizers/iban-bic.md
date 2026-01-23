## Iban/Bic

This *Anonymizer* is multicolumn. It let you anonymize, at once, an IBAN and a BIC code.
You can choose to anonymize either one of IBAN or BIC, or both.

Available options:
- `country`: (string) two-letters country code for generating the IBAN number with the target country
  validation rules (default is `fr`),
- `sample_size`: (int) generated random sample table size (default is 500).

Available columns are:

| Part   | Definition                            | Example                     |
|--------|---------------------------------------|-----------------------------|
| `iban` | The International Bank Account Number | FR1711881618378130962836522 |
| `bic`  | The Bank Identifier Code              | QFWXOC6L                    |

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            iban: # An arbitrary key
                target: table
                anonymizer: iban-bic
                options:
                    iban: 'account_iban'
                    bic: 'account_bic'
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
#[Anonymize(type: 'iban-bic', options: [ // [!code ++]
    'iban' => 'account_iban', // [!code ++]
    'bic' => 'account_bic' // [!code ++]
])] // [!code ++]
class Customer
{
    // ...

    #[ORM\Column(length: 255)]
    private ?string $accountIban = null;

    #[ORM\Column(length: 255)]
    private ?string $accountBic = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml
customer:
    iban: # An arbitrary key
        target: table
        anonymizer: iban-bic
        options:
            iban: 'account_iban'
            bic: 'account_bic'
  #...
```
:::

:::warning
This anonymizer works at the *table level* which means that the PHP attribute
cannot target object properties: you must specify table column names and not
PHP class property names.
:::

@@@
