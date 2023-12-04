# Anonymization

The *DbToolsBundle* provides a convinient way to anonymize data from your database.

After some configurations, launching `console db-tools:anonymize` will be all you need to
replace sensitive data by random and/or hashed ones in your database.

There is two ways to tell the *DbToolsBundle* how it should anonymize your data:

1. you can use **PHP attributes** on Doctrine Entities' classes and properties
2. you can declare it with a **YAML** file

::: tip
The *DbToolsBundle* does not only work with Doctrine Entities to anonymize data. You can use it with
*any* database, all you need is a DBAL connection.

In such case, the [YAML configuration](../configuration#anonymization) is the only available option.
:::

If Doctrine ORM is enabled, the *DbToolsBundle* will automatically look for attributes on your entities.
If you want to use YAML configuration, look at the [Bundle Configuration
section](../configuration#anonymization) to see how to configure it.

The anonymization is based on *Anonymizers*. An *Anonymizer* represents a way to anonymize a column (or
multiple columns in certain cases). For example, you will use the EmailAnonymizer to anonymize a column that
represents an email address.

Each *Anonymizer* could take options to specify how it should work.

In others word, all you need to do to configure Anonymization is map each column you want to anonymize with a specific anonymizer.

## Example

The best way to understand how it works is to see a simple example: let's take an entity `User`.

This entity has several fields we want to anonymize, and others that we don't:

- `id`: Serial
- `emailAddress`: An email address **that we want to anonymize**
- `age`: An integer **that we want to randomize**
- `level`: A string ('none', 'bad', 'good' or 'expert') **that we want to randomize**
- `secret`: A string **that we want to hash**
- `lastLogin`: A DateTime we want to keep intact

Here is how you can declare this configruation with PHP attributes and with YAML:

::: code-group
```php [Attribute]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Anonymize(type: 'email')] // [!code ++]
    private ?string $emailAddress = null;

    #[ORM\Column]
    #[Anonymize(type: 'integer', options: ['min' => 10, 'max' => 99])] // [!code ++]
    private ?int $age = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'string', options: ['sample' => ['none', 'bad', 'good', 'expert']])] // [!code ++]
    private ?string $level = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'md5')] // [!code ++]
    private ?string $secret = null;

    #[ORM\Column]
    private ?\DateTime $lastLogin = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml
user:
    email_address: email
    level:
        anonymizer: string
        options: {sample: ['none', 'bad', 'good', 'expert']}
    age:
        anonymizer: integer
        options: {min: 10, max: 99}
    secret: md5
#...
```
:::

## Multicolumn Anonymizers

Some *Anonymizers* are mutlicolumn. For example the *AddressAnonymizer* can, by himself, anonymize 6 columns.

*Multicolumn anonymizers* are usefull when you want to keep coherent data after anonymization.

When using PHP attributes, those anonymizers should be put on class:

::: code-group
```php [Attribute]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: '`user`')]
#[Anonymize(type: 'address', options: [ // [!code ++]
    'street_address' => 'street', // [!code ++]
    'secondary_address': 'street_second_line' // [!code ++]
    'postal_code' => 'zip_code', // [!code ++]
    'locality' => 'city', // [!code ++]
    'region' => 'region' // [!code ++]
    'country' => 'country', // [!code ++]
])] // [!code ++]
class User
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
user:
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

## Going further

The DbToolsBundle provides a bunch of *Anonymizers* that should cover most of your needs. You can find a
complete description of each one of them in the next section.

You can also add *Anonymizers* from [community packs](./packs). For example, to add the `pack-fr-fr` run:

```bash
composer require db-tools-bundle/pack-fr-fr
```

If you can't find what you need from core anonymizers and in available packs, the *DbToolsBundle* allows
you to [create your own *Custom Anonymizers*](./custom-anonymizers).

::: tip
You can list all available *Anonymizers* with `console db-tools:anonymization:list` command.
:::
