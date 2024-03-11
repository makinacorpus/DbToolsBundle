# Performance

Before we created the DbToolsBundle, we looked at other PHP tools to anonymize
data. After a quick search, we can't find a project that seems to be over the
top and most of the solutions we found was unmaintained.

Also, all these projects used the same methodology:
1. Load the data from the database
2. Anonymize it in PHP
3. Persit updated data in database

This approach has a benefit: you can use an external library - such as
[Faker](https://github.com/fzaninotto/Faker) - to anonymize your data.
But it also has a big drawback: it is very slow.

For the DbToolsBundle, we choose another methodology: **we anonymize with SQL
queries**. We use Database management system for what they are good at: processing
huge amount of data.

:::info
Anonymizing through SQL, the DbToolsBundle generates long and complexe
update queries. To get great performance for each one of the platfrom it adresses,
a minitious work has been made. Queries are optimised differently depending on the
database platform on which they will be executed.

[Learn more about these optimizations](./internals).
:::

We get pretty good results with this approach. And to demonstrate the DbToolsBnulde
capabilities, we created a [benchmark app](https://github.com/DbToolsBundle/benchmark-app).

In this app, you will find a Symfony application that uses 4 different
DBAL Doctrine connections (SQLite, PostgreSQL, MariaDb and MySQL).

For each one of these connections, we defined the same 3 entities : Customer, Address and Order.

::: code-group
```php [Customer]

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'email')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'password')]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'lastname')]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'firstname')]
    private ?string $firstname = null;

    #[ORM\Column]
    #[Anonymize(type: 'integer', options: ['min' => 10, 'max' => 99])]
    private ?int $age = null;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Address::class, orphanRemoval: true)]
    private Collection $addresses;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Order::class, orphanRemoval: true)]
    private Collection $orders;

    // ...
}
```

```php [Address]
#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[Anonymize(type: 'address', options: [
    'street_address' => 'street',
    'postal_code' => 'zip_code',
    'locality' => 'city',
    'country' => 'country',
])]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\Column(length: 255)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    //...
}
```

```php [Order]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'fr-fr.phone')]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'email')]
    private ?string $email = null;

    #[ORM\Column]
    #[Anonymize(type: 'float', options: ['min' => 10, 'max' => 99])]
    private ?float $amount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $billingAddress = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $shippingAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Anonymize(type: 'lorem')]
    private ?string $note = null;

    //...
}
```
:::

Then, we executed the `db-tools:anonymize` command on different backups. Here are the results:

| Backup description                                    | PostgreSQL | SQLite | MariaDb | MySQL
|-------------------------------------------------------|------------|--------|---------|---------
| 100K Customers alone                                  | ~5s        | ~7s    | ~20s    | ~53s
| 500K Customers alone                                  | ~9s        | ~10s   | ~37s    | ~3m44s
| 1&nbsp;000K Customers alone                           | ~16s       | ~16s   | ~1m23s  | ~36m56s
| 200K Addresses alone                                  | ~6s        | ~10s   | ~26s    | ~42s
| 1&nbsp;000K Orders alone                              | ~16s       | ~11s   | ~1m15s  | ~25m31s
| 100K Customers and 200K Addresses                     | ~7s        | ~10s   | ~32s    | ~1m16s
| 100K Customers, 200K Addresses and 1&nbsp;000K Orders | ~24s       | ~25s   | ~1m40s  | ~36m47s

<small>
**NB**: Each database vendor docker image has been used as is. Without any tweaking.
This could explain the bad results for MySQL.
</small>
