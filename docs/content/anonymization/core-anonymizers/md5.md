## Md5Anonymizer

This *Anonymizer* will fill configured column with a md5 hash of the pre-anonymization value.

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            my_dirty_secret: md5
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
    #[Anonymize(type: 'md5')] // [!code ++]
    private ?string $myDirtySecret = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    my_dirty_secret: md5
#...
```
:::

@@@

:::info
Hashing a string is not anonymizing it because hash functions have a reproducible output.
In order to avoid decrypting data using rainbow tables, a salt will be added by default to
string values prior to hashing.

Salt is global across the same anonymization run, which means that same values
across the database will all inherit from the same hashed value, keeping things consistent.

In order to disable the salt usage, set the `use_salt` option to `false`.
:::

:::warning
SQLite does implement `MD5()` function, neither any hashing function, this anonymizer
cannot be used with SQLite.
:::
