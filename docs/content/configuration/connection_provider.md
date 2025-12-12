# Standalone connection provider

When dealing with edge case environement where a static database URL cannot
work, the *connection provider* feature may help you.

Basic idea is to delegate the database connection URL string to a custom function.

:::warning
This feature is experimental and API is subject to change in the future.
:::

## Basic example

Use case: you are working in a multi-tenant environment, each client database
has the same database schema, but has different database credentials.

Let's consider you already have a central component you can access using PHP code
which allows you to find a client database connection:

```php
namespace MyApp;

class MyClientInstanceRegistry
{
    public static function getDatabaseCredentialsForClient(string $id): array;
}
```

We are making this example simple for educational purpose, but in real life
use cases you probably will have a more complex component initialization process
for this.

And you have the following anonymization configuration:

```yaml
connections:
    any_client: pgsql://clientid:clientpassword@somehostname/client1

anonymization:
    any_client:
        users:
            lastname: fr-fr.lastname
```

Your main obstacle here is to give the correct database URL for the client you
with to act upon. Let's consider that your solution is to use an environment
variable for acting upon each database independently, such as:

```sh
CLIENT_ID="client1" vendor/bin/db-tools anonymize
```

Following chapter will guide you throught three different methods to achieve this.

## Implementing the connection provider interface

You can implement the `ConnectionProvider` interface as such:

```php
namespace MyApp\DbToolsBundle;

use MakinaCorpus\DbToolsBundle\Bridge\Standalone\ConnectionProvider;

class MyClientConnectionProvider implements ConnectionProvider
{
    #[\Override]
    public function createConnectionDsn(string $name): string
    {
        if (!$clientId = getenv('CLIENT_ID')) {
            throw new \RuntimeException("Did you forget to set the CLIENT_ID environment variable?");
        }

        $credentials = MyClientInstanceRegistry::getDatabaseCredentialsForClient($clientId);

        return \sprintf(
            'pgsql://%s:%s@%s:%d/%s?some_option=some_value',
            \rawurlencode($credentials['username']),
            \rawurlencode($credentials['password']),
            \rawurlencode($credentials['hostname']),
            $credentials['port'],
            \rawurlencode($credentials['database']),
        );
    }
}
```

Then adapt your YAML configuration:

```yaml
connections:
    any_client: MyApp\DbToolsBundle\MyClientConnectionProvider
```

## Using an invokable class

You otherwise simply can write any class implenting the `__invoke()` method:

```php
namespace MyApp\DbToolsBundle;

class MyInvokableClientConnectionProvider
{
    public function __invoke()
    {
        if (!$clientId = getenv('CLIENT_ID')) {
            throw new \RuntimeException("Did you forget to set the CLIENT_ID environment variable?");
        }

        $credentials = MyClientInstanceRegistry::getDatabaseCredentialsForClient($clientId);

        return \sprintf(
            'pgsql://%s:%s@%s:%d/%s?some_option=some_value',
            \rawurlencode($credentials['username']),
            \rawurlencode($credentials['password']),
            \rawurlencode($credentials['hostname']),
            $credentials['port'],
            \rawurlencode($credentials['database']),
        );
    }
}
```

Then adapt your YAML configuration:

```yaml
connections:
    any_client: MyApp\DbToolsBundle\MyInvokableClientConnectionProvider
```

## Using a class static method

You can use an object static method:

```php
namespace MyApp\SomeNamespace;

class SomeExistingClass
{
    // ... your other code (or not).

    public static function myDbToolsConnectionProvider()
    {
        if (!$clientId = getenv('CLIENT_ID')) {
            throw new \RuntimeException("Did you forget to set the CLIENT_ID environment variable?");
        }

        $credentials = MyClientInstanceRegistry::getDatabaseCredentialsForClient($clientId);

        return \sprintf(
            'pgsql://%s:%s@%s:%d/%s?some_option=some_value',
            \rawurlencode($credentials['username']),
            \rawurlencode($credentials['password']),
            \rawurlencode($credentials['hostname']),
            $credentials['port'],
            \rawurlencode($credentials['database']),
        );
    }
}
```

Then adapt your YAML configuration:

```yaml
connections:
    any_client: MyApp\SomeNamespace\SomeExistingClass::myDbToolsConnectionProvider
```

## Using an arbitrary function

Or simply use any PHP function which was loaded using the autoloader:

```php
function my_dbtools_client_connection_provider(): string
{
    if (!$clientId = getenv('CLIENT_ID')) {
        throw new \RuntimeException("Did you forget to set the CLIENT_ID environment variable?");
    }

    $credentials = MyClientInstanceRegistry::getDatabaseCredentialsForClient($clientId);

    return \sprintf(
        'pgsql://%s:%s@%s:%d/%s?some_option=some_value',
        \rawurlencode($credentials['username']),
        \rawurlencode($credentials['password']),
        \rawurlencode($credentials['hostname']),
        $credentials['port'],
        \rawurlencode($credentials['database']),
    );
}
```

Then adapt your YAML configuration:

```yaml
connections:
    any_client: my_dbtools_client_connection_provider
```

## Notes

In all cases, the method signature function is always the same `connection_provider(string $name): string`.

You may omit the `$name` parameter if you don't intend to use it. It contains
the connection name as specified in the YAML configuration `connections` value.
