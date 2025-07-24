## File multiple column

This Anonymizer will anonymize multiple columns at once using value rows from a
input file. As of now, only CSV files are supported.

This aninymizer behaves like any other multiple column anonymizer and allows you
to arbitrarily map any sample column into any database table column using the
anonymizer options.

Given the following file:

```txt
Number,Foo,Animal
1,foo,cat
2,bar,dog
3,baz,girafe
```

Then:

@@@ standalone docker

```yaml [YAML]
# db_tools.config.yaml
anonymization:
    default:
        customer:
            my_data:
                anonymizer: file_column
                options:
                    source: ./resources/my_data.csv
                    # Define your CSV file column names.
                    columns: [number, foo, animal]
                    # Other allowed options.
                    file_skip_header: true
                    # Now your columns, keys are CSV column names
                    # you set upper, values are your database column
                    # names.
                    number: my_integer_column
                    foo: my_foo_column
                    animal: my_animal_column
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
#[Anonymize(type: 'string', options: [ // [!code ++]
    'source' => './resources/my_data.csv', // [!code ++]
    // Define your CSV file column names. // [!code ++]
    'columns': ['number', 'foo', 'animal'], // [!code ++]
    // Other allowed options. // [!code ++]
    'file_skip_header' => true, // [!code ++]
    // Now your columns, keys are CSV column names // [!code ++]
    // you set upper, values are your database column // [!code ++]
    // names. // [!code ++]
    'number' => 'my_integer_column', // [!code ++]
    'foo' => 'my_foo_column', // [!code ++]
    'animal' => 'my_animal_column', // [!code ++]
])] // [!code ++]
class Customer
{
    // ...

    #[ORM\Column(length: 255)]
    private ?string $myNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $myFoo = null;

    #[ORM\Column(length: 255)]
    private ?string $myAnimal = null;

    // ...
}
```

```yaml [YAML]
# config/anonymization.yaml
customer:
    my_data:
        anonymizer: file_column
        options:
            source: ./resources/my_data.csv
            # Define your CSV file column names.
            columns: [number, foo, animal]
            # Other allowed options.
            file_skip_header: true
            # Now your columns, keys are CSV column names
            # you set upper, values are your database column
            # names.
            number: my_integer_column
            foo: my_foo_column
            animal: my_animal_column
  #...
```
:::

:::warning
This anonymizer works at the *table level* which means that the PHP attribute
cannot target object properties: you must specify table column names and not
PHP class property names.
:::

@@@

When parsing a file file, you can set the following options as well:
  - `file_csv_enclosure`: if file is a CSV, use this as the enclosure character (default is `'"'`).
  - `file_csv_escape`: if file is a CSV, use this as the escape character (default is `'\\'`).
  - `file_csv_separator`: if file is a CSV, use this as the separator character (default is `','`).
  - `file_skip_header`: when reading any file, set this to true to skip the first line (default is `false`).

:::tip
The filename can be absolute, or relative. For relative file resolution
please see [*File name resolution*](#file-name-resolution)
:::
