# Custom Anonymizers

The *DbToolsBundle* allows you to create your own *Anonymizers*.

To create one, you only have to add a class in the `src/Anonymizer` directory
that extends `AbstractAnonymizer` and add the `AsAnonymizer` attribute.

```php
// src/Anonymizer/MyAnonymizer.php

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

::: tip
You can tell the *DbToolsBundle* your *Custom Anonymizers* live in a different directory
with the [*Anonymizer paths* configuration](../configuration#anonymizer-paths).
:::
