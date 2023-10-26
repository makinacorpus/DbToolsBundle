# Pack FR_Fr

This page list the common purpose *Anonymizers* provided by the *DbToolsBundle*.

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

    #[ORM\Column(length: 255)] // [!code focus]
    #[Anonymize('fr_fr.lastname')] // [!code focus]
    private ?string $lastname = null; // [!code focus]

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

    #[ORM\Column(length: 255)] // [!code focus]
    #[Anonymize('fr_fr.firstname')] // [!code focus]
    private ?string $firstname = null; // [!code focus]

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

    #[ORM\Column(length: 255)] // [!code focus]
    #[Anonymize('fr_fr.phone', ['mode' => 'landline'])] // [!code focus]
    private ?string $telephoneFixe = null; // [!code focus]

    #[ORM\Column(length: 255)] // [!code focus]
    #[Anonymize('fr_fr.phone')] // [!code focus]
    private ?string $telephoneMobile = null; // [!code focus]

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