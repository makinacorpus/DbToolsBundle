# Pack FR_Fr

This page list the common purpose *Anonymizers* provided by the *DbToolsBundle*.

## NomAnonymizer

Works like the StringAnonymizer, but with a provided sample of 500 french lastnames.

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      user:
        lastname: MakinaCorpus\DbToolsBundle\Anonymizer\FrFR\NomAnonymizer
  #...
```

## PrenomAnonymizer

Works like the StringAnonymizer, but with a provided sample of 500 french firstnames.

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      user:
        firstname: MakinaCorpus\DbToolsBundle\Anonymizer\FrFR\PrenomAnonymizer
  #...
```

## PhoneAnonymizer

Generates random french phone numbers, using reserved prefixes dedicated to
fictional usage (those phone numbers will never exist).

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      user:
        telephone_fixe:
          anonymizer: fr_fr.phone
          options:
            # either 'landline' or 'mobile' (default is 'mobile')
            mode: landline
        telephone_mobile: phone
  #...
```
