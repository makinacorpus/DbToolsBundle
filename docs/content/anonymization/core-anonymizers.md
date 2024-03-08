# Core Anonymizers

This page list all *Anonymizers* provided by *DbToolsBundle*.

[[toc]]

## EmailAnonymizer

EmailAnonymizer uses a hash function on the original value to make each unique email
anonymization reproducible accross tables.

This *Anonymizer* will fill configured column with value looking like `[username]@[domain.tld]`
where:
* `[username]` is a md5 hash of the pre-anonymization value
* `[domain.tld]` is the given domain option (or `example.com` by default)

For example `contact@makina-corpus.com` will give `826464d916e6052ad209037ca71ce324@example.com` after anonymization.

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

Or, with the domain option:

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

## PasswordAnonymizer

This *Anonymizer* give you a way to set the same password for each one of your users. It is based on
the [Symfony PasswordHasher Component](https://symfony.com/doc/current/security/passwords.html).

Options are :

- `algorithm`: algorithm to use to hash the plain password. (Default is `auto`).
- `password`: plain password that will be set for each row. (Default is `password`)

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

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Anonymize(type: 'password')] // [!code ++]
    private ?string $password = null;

    // Or, with options:

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Anonymize(type: 'password', options: ['algorithm' => 'sodium', 'password' => '123456789'])] // [!code ++]
    private ?string $password = null;
    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    password: password


# Or, with options:

customer:
    password:
        anonymizer: password
        options: {algorithm: 'sodium', password: '123456789'}

#...
```
:::

## IntegerAnonymizer

Anonymize integers by:
- randomly choosing an integer in a range delimited by 'min' and 'max' options
- altering the initial value by adding it a random value picked in a range calculated from the 'delta' or 'percent' options

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
    // Will fill the age column with a random integer // [!code ++]
    // in the [min, max] interval // [!code ++]
    #[Anonymize(type: 'integer', options: ['min' => 10, 'max' => 99])] // [!code ++]
    private ?int $age = null;

    #[ORM\Column]
    // Will add to each age value a random integer // [!code ++]
    // in the [-delta, +delta] interval // [!code ++]
    // In this example, an integer between -15 and 15 will be // [!code ++]
    // added to the initial value // [!code ++]
    #[Anonymize(type: 'integer', options: ['delta' => 15])] // [!code ++]
    private ?int $age = null;

    #[ORM\Column]
    // Will add to each age value a random percent // [!code ++]
    // of the initial value in the [-percent%, +percent%] interval // [!code ++]
    // In this example, a value between -10% and 10% of the initial value // [!code ++]
    // will be added to age. // [!code ++]
    #[Anonymize(type: 'integer', options: ['percent' => 10])] // [!code ++]
    private ?int $age = null;
    // ...
}
```

```yml [YAML]
# config/anonymization.yaml

customer:
    age:
        anonymizer: integer
        options: {min: 10, max: 99}

#...
```
:::

## FloatAnonymizer

This *Anonymizer* will fill configured column with a random float between two values and a given precision (default 2).

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
    #[Anonymize(type: 'float', options: ['min' => 10, 'max' => 99, 'precision' => 4])] // [!code ++]
    private ?float $size = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    size:
        anonymizer: float
        options: {min: 120, max: 300, precision: 4}
#...
```
:::

## Md5Anonymizer

This *Anonymizer* will fill configured column with a md5 hash of the pre-anonymization value.

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

## StringAnonymizer

This *Anonymizer* will fill configured column with a random value from a given sample.

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

:::tip
If you use the same sample multiple times, if you use a large sample or if you use a generated one, it could be
more efficient and convinient to create your own custom anonymizer, see the [Custom Anonymizers](/anonymization/custom-anonymizers)
section to learn how to do that.
:::

## LastnameAnonymizer

Works like the StringAnonymizer, but with a provided sample of 1000 worldwide lastnames.

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

## FirstnameAnonymizer

Works like the StringAnonymizer, but with a provided sample of 1000 worldwide firstnames.

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

## AddressAnonymizer

This *Anonymizer* is multicolumn. It let you anonymize, at once, mutiple columns on one table
that represent different parts of a postal address.

Each part will be fill with a coherent random address from a sample of 300 addresses around the world.

Available parts are :

| Part                 | Definition                                                              | Example            |
|----------------------|-------------------------------------------------------------------------|--------------------|
| `country`            | The country                                                             | France             |
| `locality`           | The locality in which the street address is, and which is in the region | Nantes             |
| `region`             | The region in which the locality is, and which is in the country        | Pays de la Loire   |
| `postal_code`        | The postal code                                                         | 44000              |
| `street_address`     | The street address. For example, 5 rue de la Paix                       | 5 rue de la Paix   |
| `secondary_address`  | Additional information (apartment, block)                               | Appartement 310    |

::: code-group
```php [Attribute]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: 'customer')]
#[Anonymize(type: 'address', options: [ // [!code ++]
    'street_address' => 'street', // [!code ++]
    'secondary_address': 'street_second_line' // [!code ++]
    'postal_code' => 'zip_code', // [!code ++]
    'locality' => 'city', // [!code ++]
    'region' => 'region' // [!code ++]
    'country' => 'country', // [!code ++]
])] // [!code ++]
class Customer
{
    // ...

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\Column(length: 255)]
    private ?string $streetSecondLine = null;

    #[ORM\Column(length: 255)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $region = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml
customer:
    address:
        target: table
        anonymizer: address
        options:
            street_address: 'street'
            secondary_address: 'street_address_2'
            postal_code: 'zip_code'
            locality: 'city'
            region: 'region'
            country: 'country'
  #...
```
:::

:::tip
Note that you don't have to provide a column for each part. You can use this *Anonymizer* to
only anonymize some parts of an address. To do so, remove options you don't want in the example below.
:::