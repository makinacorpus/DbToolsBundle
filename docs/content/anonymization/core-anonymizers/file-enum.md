## File enum

This anonymizer will fill configured column with a random value from a given sample fetched
from a plain text or a CSV file.

Given the following file:

```txt
none
bad
good
expert
```

Then:

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            level:
                anonymizer: file_enum
                options: {source: ./resources/levels.txt}
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
    #[Anonymize(type: 'string', options: ['source' => "./resources/levels.txt"])] // [!code ++]
    private ?string $level = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml
customer:
    level:
        anonymizer: file_enum
        options: {source: ./resources/levels.txt}
#...
```
:::

@@@

File will be read this way:
 - When using a plain text file, each line is a value, no matter what's inside.
 - When using a CSV file, the first column will be used instead.

When parsing a file file, you can set the following options as well:
  - `file_csv_enclosure`: if file is a CSV, use this as the enclosure character (default is `'"'`).
  - `file_csv_escape`: if file is a CSV, use this as the escape character (default is `'\\'`).
  - `file_csv_separator`: if file is a CSV, use this as the separator character (default is `','`).
  - `file_skip_header`: when reading any file, set this to true to skip the first line (default is `false`).

:::tip
The filename can be absolute, or relative. For relative file resolution
please see [*File name resolution*](#file-name-resolution)
:::
