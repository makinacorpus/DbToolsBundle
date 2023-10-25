# Custom Anonymizers

The *DbToolsBundle* allows you create your own *Anonymizers*.

By default, the *DbToolsbundle* will look for *Anonymizers* in 'src/Anonymizer' folder.
To add a new one, you only have to create a class that extends `MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer` with
the `AsAnonymizer` attribute and put it in this folder like this:

```php
<?php

declare(strict_types=1);

namespace App\Anonymizer;

use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'my_anonymizer', // a snake case string
    pack: 'my_app', // a snake case string
    description: <<<TXT
    Describe here if you want how your anonymizer works.
    TXT
)]
class MyAnonymizer extends AbstractAnonymizer
{

  // ...
```

To understand how an `Anonymizer` works, read `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/AbstractAnonymizer.php`
which is self-documented.

To inspire you, browse existing *Anonymizers* in:

* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/Core`
* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/FrFR`