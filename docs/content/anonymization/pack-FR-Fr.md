# Pack FR_Fr

This page list the common purpose *Anonymizers* provided by the *DbToolsBundle*.

[[toc]]

## LastnameAnonymizer

Same as LastnameAnonymizer from core, but with a provided sample of 500 french lastnames.

::: code-group
```php [Attribute]
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: '`user`')]
class User
{
    // ...

    #[ORM\Column(length: 255)]
    #[Anonymize('fr_fr.lastname')] // [!code ++]
    private ?string $lastname = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

user:
    lastname: fr_fr.lastname
#...
```
:::

## FirstnameAnonymizer

Same as FirstnameAnonymizer from core, but with a provided sample of 500 french firstnames.

::: code-group
```php [Attribute]
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: '`user`')]
class User
{
    // ...

    #[ORM\Column(length: 255)]
    #[Anonymize('fr_fr.firstname')] // [!code ++]
    private ?string $firstname = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

user:
    firstname: fr_fr.firstname
#...
```
:::

## PhoneAnonymizer

Generates random french phone numbers, using reserved prefixes dedicated to
fictional usage (those phone numbers will never exist).

Available option is `mode` which can either be `mobile` or `landline`. Default value is `mobile`.

::: code-group
```php [Attribute]
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: '`user`')]
class User
{
    // ...

    #[ORM\Column(length: 255)]
    #[Anonymize('fr_fr.phone', ['mode' => 'landline'])] // [!code ++]
    private ?string $telephoneFixe = null;

    #[ORM\Column(length: 255)]
    #[Anonymize('fr_fr.phone')] // [!code ++]
    private ?string $telephoneMobile = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

user:
    telephone_fixe:
        anonymizer: fr_fr.phone
        options:
            # either 'landline' or 'mobile' (default is 'mobile')
            mode: landline
    telephone_mobile: phone
#...
```
:::

## AddressAnonymizer

Same as AddressAnonymizer from core but with a provided sample of 500 french dumb adresses.


::: code-group
```php [Attribute]
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: '`user`')]
#[Anonymize(type: 'fr_fr.address', options: [ // [!code ++]
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
        anonymizer: fr_fr.address
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
