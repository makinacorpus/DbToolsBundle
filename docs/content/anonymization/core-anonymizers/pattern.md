## String pattern

Anonymize by building text values using other anonymizers to fill holes in text.

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            biography:
                anonymizer: pattern
                options: {value: '{firstname} {lastname} lives in {address:locality} in {address:country}, he has [2,7] cats.'}
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

    #[ORM\Column]
    #[Anonymize(type: 'pattern', options: ['value' => '{firstname} {lastname} lives in {address:locality} in {address:country}, he has [2,7] cats.'])] // [!code ++]
    private ?string $biography = null;
}
```

```yml [YAML]
# config/anonymization.yaml

customer:
    biography:
        anonymizer: pattern
        options: {value: '{firstname} {lastname} lives in {address:locality} in {address:country}, he has [2,7] cats.'}
#...
```
:::

@@@

### Integer range

By adding `[MIN,MAX]` anywhere in your text, it will be replaced by a random integer
in the given range. For example `"I want [7,111] apples"`.

You can use negative integers such as `[-10,10]` or even a fully negative integer
range `[-127,-34]`.

### Single value from another anonymizer

By adding `{ANONYMIZER}` anywhere in your text, it will be replaced by a random value
given by the anonymizer. For example `"My email address is {email}, what's yours?"`.

:::warning
The target anonymizer must not be an multi-column anonymizer.
:::

### Column value from a multi-column anonymizer

By adding `{ANONYMIZER:COLUMN}` anywhere in your text, it will be replaced by a random
value for the named column given by the anonymizer. For example, `"The country I live into is {address:country}."`.

If you use more than one column of the same anonymizer in the same text value, the same
generated row from the target anonymizer will be used. For example, this text will give
a consistent result: `"The country I live in {address:locality} in {address:country}."`.

:::warning
The target anonymizer must be a multi-column anonymizer.
:::
