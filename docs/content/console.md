# CLI tool

DbTools ships a console tool for running it as an standalone application.

If installed via composer, simply run:

```sh
./vendor/bin/db-tools
```

This CLI tool runs outside of the framework and application context. Therefore it requires
that you provide a dedicated configuration file.

:::info
An experimental script that generates a PHAR file is provided in the repository and can be
used to create a self-contained executable

Future plans are to provide an official PHAR archive for each stable release.
:::

## Configuration

### Configuration file

The configuration file is a `YAML` file whose available options are strictly identical
to the Symfony bundle configuration with a few additional parameters dedicated to the
standalone application.

:::tip
Please refer to the [configuration reference](configuration/reference) for a complete
and detailled configuration options list.
:::

The most important and required one is the list of available database connections,
using the `connections` configuration option.

```yaml [Standalone]
connections:
    connection_one: "pgsql://username:password@hostname:port?version=16.0&other_option=..."
    connection_two: "mysql://username:password@hostname:port?version=8.1&other_option=..."
```

Keys are connection names for idenfying those as a command line option, values are
database URL containing all necessary information and options for connecting.

### Environment variables

@todo

### Anonymizer mapping

Anonymizer tables and columns cannot be configured via command line options since
they require a more complex configuration. In order to use the standalone CLI for 
anonymizing, you need to provide a mapping configuration file.

File structure is the same as the documented YAML anonymization configuration file
which can be used as-is.

In order to pass configuration, use the `--anonymizer-config=` option or
`DB_TOOLS_ANONYMIZER_CONFIG=` environment variable, followed by a file relative
or absolute path.

For example:

::: code-group
```sh [Option]
./vendor/bin/db-tools --anonymizer-config=my_config.yaml
```

```sh [Env]
DB_TOOLS_ANONYMIZER_CONFIG=my_config.yaml ./vendor/bin/db-tools
```
:::

### Dumping from Symfony configuration

:::warning
When using the CLI tool, you are not in the Symfony application context anymore,
which means the CLI tool doesn't know the Symfony database configuration, doctrine
connections or doctrine ORM mapping.
:::

@todo

## Backup database



## Restore database


## Anonymize
