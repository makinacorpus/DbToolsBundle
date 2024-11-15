# Development guide

You want to add some code to *DbToolsBundle*, here is some help !

## Getting sources

The most convient way to develop on this library is to do it from an existing project.

1. Reinstall the bundle keeping Git metadata:
    `composer install --prefer-source`
2. Work directly in vendor/makinacorpus/db-tools-bundle:
    `cd vendor/makinacorpus/db-tools-bundle`
3. Create a new branch: `git checkout -b my_patch`
4. When your code is ready, fork the project and add your Git remote: `git remote add <your-name> git@github.com:<your-name>/core.git`
5. You can now push your code and open your Pull Request: `git push <your-name> my_patch`

## Devs tools

Before submitting a PR, always ensure that Coding Standards are respected and run the Static Analysis.
*DbToolsBundle* helps you doing that with the `dev.sh` script.

This tool comes with a complete docker stack to help you developing, to start to use it:

```sh
# build the docker stack
./dev.sh build
# then, start it
./dev.sh up
```

From here, you can either:

```sh
# checks coding standards and launch static analysis
./dev.sh checks

# launch phpunit tests with several database vendors and versions
./dev.sh test_all

# or launch phpunit test for a single database vendors
./dev.sh test
```

When you finish to develop, stop the stack with:

```sh
./dev.sh down
```

If you need to clean up everything (containers, images and volumes), you can use:

```sh
./dev.sh clean
```

To learn more about `dev.sh` script, launch:

```sh
./dev.sh
```

