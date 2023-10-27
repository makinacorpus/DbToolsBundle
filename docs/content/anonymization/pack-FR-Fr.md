# Pack FR_Fr

This page list the common purpose *Anonymizers* provided by the *DbToolsBundle*.

[[toc]]

## LastnameAnonymizer

Works like the StringAnonymizer, but with a provided sample of 500 french lastnames.

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

Works like the StringAnonymizer, but with a provided sample of 500 french firstnames.

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