## LoremIpsumAnonymizer

Replace a text with some *lorem ipsum*.
Default behavior is to generate a single paragraph.

Available options:
- `paragraphs`: (int) number of paragraphs to generate,
- `words`: (int) number of words to generate
  (could not be used in combination with `paragraphs` option),
- `html`: (bool) surround each paragraph with `<p>`, default is false.
- `sample_count`: (int) how many different values to use (default is 100).

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            message: lorem

        customer:
            # Will generate 10 paragraphs, each one surrounded by a html `<p>` tag
            message:
                anonymizer: lorem
                options:
                    paragaphs: 10
                    html: true

        customer:
            # Will only generate 5 words
            message:
                anonymizer: lorem
                options: {words: 5}
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
    #[Anonymize('lorem')] // [!code ++]
    private ?string $message = null;

    #[ORM\Column(length: 255)]
    // Will generate 10 paragraphs, each one surrounded by a html `<p>` tag
    #[Anonymize('lorem', ['paragraphs' => 10, 'html' => true])] // [!code ++]
    private ?string $message = null;

    #[ORM\Column(length: 255)]
    // Will only generate 5 words
    #[Anonymize('lorem', ['words' => 5])] // [!code ++]
    private ?string $message = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml

customer:
    message: lorem

customer:
    # Will generate 10 paragraphs, each one surrounded by a html `<p>` tag
    message:
        anonymizer: lorem
        options:
            paragaphs: 10
            html: true

customer:
    # Will only generate 5 words
    message:
        anonymizer: lorem
        options: {words: 5}
#...
```
:::

@@@
