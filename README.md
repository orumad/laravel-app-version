# Laravel App Version
My **_(own & personal)_** system to _update_ the **version** of Laravel applications, their **changelog** and _commit_ changes to git.

Additionally, it can merge changes from the `main`` branch to the `deploy` branch in order to _deploy_ automatically these changes to the remote server.

This package installs a Laravel console command called `update:version` that can perform all the tasks described above.

Usage:
```sh
$ php artisan update:version {message} {--T|tag=version} {--deploy}s
```
