# Laravel App Version
My **_(own & personal)_** system to _update_ the **version** of Laravel applications, their **changelog** and _commit_ changes to git.

Additionally, it can merge changes from the `main`` branch to the `deploy` branch in order to _deploy_ automatically these changes to the remote server.

This package installs a Laravel console command called `update:version` that can perform all the tasks described above.

Usage:
```sh
$ php artisan update:version {message} {--T|tag=version} {--deploy}
```

_NOTE: due to the very personal nature of this task, the repository's issues system has been disabled. Feel free to fork and make the changes you need to adapt this to your own system._
