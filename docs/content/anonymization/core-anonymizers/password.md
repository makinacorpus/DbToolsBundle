## Password

This *Anonymizer* give you a way to set the same password for each one of your users. It is based on
the [Symfony PasswordHasher Component](https://symfony.com/doc/current/security/passwords.html).

Options are :

- `algorithm`: algorithm to use to hash the plain password. (Default is `auto`).
- `password`: plain password that will be set for each row. (Default is `password`)

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            password: password


        # Or, with options:

        customer:
            password:
                anonymizer: password
                options: {algorithm: 'sodium', password: '123456789'}
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

@@@
