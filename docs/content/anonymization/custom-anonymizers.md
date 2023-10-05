# Custom Anonymizers

The *DbToolsBundle* let you create your own *Anonymizers*.

By default, the *DbToolsbundle* will look for *Anonymizers* in 'src/Anonymizer' folder.
To add a new one, you only have to create a class that extends `MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer` with
the `AsAnonymizer` attribute and put it in this folder.

To understand how an `Anonymizer` works, read `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/AbstractAnonymizer.php`
which is self-documented.

To inspire you, browse existing *Anonymizers* in:

* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/Core`
* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/FrFR`