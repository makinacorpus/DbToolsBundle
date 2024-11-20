## EmailAnonymizer

EmailAnonymizer uses a hash function on the original value to make each unique email
anonymization reproducible accross tables.

This *Anonymizer* will fill configured column with value looking like `[username]@[domain.tld]`
where:
* `[username]` is a md5 hash of the pre-anonymization value
* `[domain.tld]` is the given domain option (or `example.com` by default)

For example `contact@makina-corpus.com` will give `826464d916e6052ad209037ca71ce324@example.com` after anonymization.

<div class=standalone>

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            email_address: email
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

    #[ORM\Column(length: 180, unique: true)]
    #[Anonymize(type: 'email')] // [!code ++]
    private ?string $email = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    email_address: email

#...
```
:::

</div>

Or, with the domain option:

<div class=standalone>

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            email_address:
                anonymizer: email
                options: {domain: 'custom-domain.com'}
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

    #[ORM\Column(length: 180, unique: true)]
    #[Anonymize(type: 'email', options: ['domain' => 'custom-domain.com'])] // [!code ++]
    private ?string $email = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    email_address:
        anonymizer: email
        options: {domain: 'custom-domain.com'}
#...
```
:::

</div>

:::info
Email value is salted prior to be hashed using md5 in order to prevent reverse hashing
with rainbow tables. Salt is global across the same anonymization run, this means that
the same email address anonymized twice will give the same value.

In order to disable the salt, set the `use_salt` option to false.
:::

:::warning
SQLite does implement `MD5()` function, neither any hashing function: in order to get
around this, the `rowid` value is used instead which prevent email values anonymization
from being reproducible across tables.
:::
