# Pack FR_Fr

This page list the common purpose *Anonymizers* provided by the *DbToolsBundle*.

## LastnameAnonymizer

Works like the StringAnonymizer, but with a provided sample of 500 french lastnames.

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      user:
        lastname: fr_fr.lastname
  #...
```

## FirstnameAnonymizer

Works like the StringAnonymizer, but with a provided sample of 500 french firstnames.

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      user:
        firstname: fr_fr.firstname
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
