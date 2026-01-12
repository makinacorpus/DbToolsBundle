## Address

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

@@@ standalone docker
```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            address: # An arbitrary key
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
@@@
@@@ symfony
::: code-group
```php [Attribute]
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity()]
#[ORM\Table(name: 'customer')]
#[Anonymize(type: 'address', options: [ // [!code ++]
    'street_address' => 'street', // [!code ++]
    'secondary_address' => 'street_second_line' // [!code ++]
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
    address: # An arbitrary key
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
@@@

:::tip
Note that you don't have to provide a column for each part. You can use this *Anonymizer* to
only anonymize some parts of an address. To do so, remove options you don't want in the example below.
:::
