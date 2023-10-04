# Custom Anonymizers

The *DbToolsBundle* let you create your own *Anonymizers*.

To do so, you only have to create a class that extends `MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer`.

To understand how an `Anonymizer` works, read `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/AbstractAnonymizer.php`
which is self-documented.

To inspire you, browse existing *Anonymizers* in:

* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/Common`
* `vendor/makinacorpus/db-tools-bundle/src/Anonymizer/FrFR`