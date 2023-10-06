# Core Anonymizers

This page list the common purpose *Anonymizers* provided by the *DbToolsBundle*.

## EmailAnonymizer

This *Anonymizer* will fill configured column with value looking like `[username]@[domain.tld]`
where:
* `[username]` is a md5 hash of the pre-anonymization value
* `[domain.tld]` is the given domain option (or `example.com` per default)

For example `contact@makina-corpus.com` will give `826464d916e6052ad209037ca71ce324@example.com` after anonymization.

Considering you want to anonymize column `email_address` of table `user`, from the `default` doctrine connection,
you can configure it like this:

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      user:
        email_address: email
  #...
```

Or like this, with the domain option

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      user:
        email_address:
          anonymizer: email
          options: {domain: 'custom-domain.com'}
  #...
```

## IntegerAnonymizer

This *Anonymizer* will fill configured column with a random integer between two values.

Considering you want to anonymize column `age` of table `user`, from the `default` doctrine connection
and you want to put in it a random integer between `10`, `99`, you can configure it like this:

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      age:
        anonymizer: integer
        options: {min: 10, max: 99}
  #...
```

## FloatAnonymizer

This *Anonymizer* will fill configured column with a random float between two values and a given precision (default 2).

Considering you want to anonymize column `size` of table `user`, from the `default` doctrine connection
and you want to put in it a random integer between `120`, `300`, with a precision of `4` digits you can
configure it like this:

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      age:
        anonymizer: float
        options: {min: 120, max: 300, precision: 4}
  #...
```

## Md5Anonymizer

This *Anonymizer* will fill configured column with a md5 hash of the pre-anonymization value.

Considering you want to hash column `my_dirty_secret` of table `user`, from the `default` doctrine connection,
you can configure it like this:

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      my_dirty_secret: md5
  #...
```

## StringAnonymizer

This *Anonymizer* will fill configured column with a random value from a given sample.

Considering you want to anonymize column `level` of table `user`, from the `default` doctrine connection
and you want to put in it a random value from `none`, `bad`, `good` or `expert`, you can configure it like this:

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
    default:
      level:
        anonymizer: string
        options: {sample: ['none', 'bad', 'good', 'expert']}
  #...
```

:::warning
If you use the same sample on multiple columns or if you use a large sample, it could be more efficient and convinient
to create your own custom anonymizer, see the [Custom Anonymizers](/anonymization/custom-anonymizers) section to learn
how to do that.
:::

## AddressAnonymizer

This *Anonymizer* is multi-column. It let you anonymize, at once, mutiple columns on one table
that represent different parts of a postal address.

Each part will be fill with a coherent random address from a sample 300  addresses around the world.

Available parts are :

| Part                 | Definition                                                              | Example            |
|----------------------|-------------------------------------------------------------------------|--------------------|
| `country`            | The country                                                             | France             |
| `locality`           | The locality in which the street address is, and which is in the region | Nantes             |
| `region`             | The region in which the locality is, and which is in the country        | Pays de la Loire   |
| `postal_code`        | The postal code                                                         | 44000              |
| `street_address`     | The street address. For example, 5 rue de la Paix                       | 5 rue de la Paix   |
| `secondary_address`  | Additional information (apartment, block)                               | Appartement 310    |


Considering you want to anonymize colmuns that represent an address on table `user`, from the `default` doctrine connection,
you can configure it like this:

```yml
# config/packages/db_tools.yaml

db_tools:
  #...
  anonymization:
   address:
          target: table
          anonymizer: address
          options:
            street_address: 'street'
            secondary_address: 'street_address_2'
            postal_code: 'zip_code'
            locality: 'city'
            region: 'region'
            country: 'country'
  #...
```

:::tip
Note that you don't have to provide a column for each part. You can use this *Anonymizer* to
only anonymize some parts of an address. To do so, just remove options you don't want in the example below.
:::