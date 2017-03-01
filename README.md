# Phoenix
*An update helper*

"a phoenix (..) is a long-lived bird that is cyclically regenerated or reborn." &mdash; [Wikipedia](http://en.wikipedia.org/wiki/Phoenix_%28mythology%29)

To install an `$archive` by use of a [phoenix.json](./phoenix.json)-file:

```php
$P = new Phoenix( dirname(__FILE__).'/phoenix.json' );
$P->upgrade(TRUE); //automatically creates $archive when not already exists
```

To manually install or update a particular `$archive`:

```php
$mount = "/www/your/tools-directory/$archive/";
$src = "http://the.location.wh/ere/you/found/the/$archive.zip";
$P = new Phoenix($mount=NULL, $src=FALSE, $create=FALSE);
//To install the particular $archive:
$P->install($P->download($src));
//next time:
$P->upgrade(FALSE);
```

It is easy to synchronize archives with Phoenix. You should use an [phoenix.json](./phoenix.json)-file, to configure, like:
```json
[
{"name" : "$archive",
"mount": "/www/your/tools-directory/$archive/",
"src": "http://the.location.wh/ere/you/found/the/$archive.zip"}
]
```

As you might notice `$mount` is an absolute path. To be more flexible and compliant to the framework (like [Hades](https://github.com/sentfanwyaerda/Hades/)), you could assign `$type` as `'tool'` or `'javascript'` or `'skin'`, or any other available hotkey.

When `$src` is within the [GitHub](https://github.com/)-domain, the link can be analysed to provide more options. Try `Phoenix::get_github_data($src);`.


## Phoenix workflow
1. Checks if `$mount` exists, to determine if it could already be installed.
2. If installed; when testing-functionality is available, it tests the functionality of the current install and saves the results.
3. Identifies the target by `$src`. If it is on [Github](http://github.com/) it analyses the current version: gets releases and branches and statistics (issues, forks, stargazers), and data of the current commit (sha, comment, author).
4. If installed; it determines if update is available and reports a notification.
5. Phoenix downloads the current release.
6. Phoenix compares the current release to the current install (or an previous to current), to determine custumizations. If custumized, it back-ups the actual installation, and requires a manual upgrade.
7. Phoenix updates to the current release. (optionally could this be automated)
8. Phoenix tests the (newly) current release and compares the results. If it fails on critical tests, it automatically rolls-back to the (previous) current install.
9. Phoenix reports a notification of success.

Currently only steps 3, 5, 7 of the workflow are available and test-worthy.

## Documentation

### Phoenix($phoenix_file, $auto=FALSE)
This function is a configuration short of the *auto upgrade* procedure.

### new Phoenix()

### Phoenix::install()
*alias: `Phoenix::git_clone()`*

### Phoenix::upgrade($force=FALSE)
*alias: `Phoenix::git_pull()`*

### Phoenix::backup()

### Phoenix::stall()

### Phoenix::revert($to)
*alias: `Phoenix::restore()`*

### Phoenix::uninstall()

### Phoenix::download()

### Phoenix::fingerprint()

### Phoenix::fingerprint_diff()

### Phoenix::get_github_data($src)

### (bool) Phoenix::is_authenticated()
To provide a shell of security (e.g. test if the user is authenticated and has `administrator` rights), Phoenix uses [Heracles](https://github.com/sentfanwyaerda/Heracles/) for this task.

**WARNING**: when [Heracles](https://github.com/sentfanwyaerda/Heracles) is not available it will return `TRUE`.

### (bool) Phoenix::is_enabled()

### (bool) Phoenix::upgrade_available()

### (bool) Phoenix::git_enabled()

### PHOENIX_ARCHIVE
The constant `PHOENIX_ARCHIVE` sets the directory where all downloaded repositories are saved, and where the (overall) [phoenix.json](./phoenix.json) database is located. If you need to deviate from `dirname(dirname(__FILE__))`, make sure to set this constant before loading the Phoenix-library.

### Phoenix::getIndexByName($archive)

### [phoenix.json](./phoenix.json)
```json
[
   {"name":"Phoenix","src":"https://github.com/sentfanwyaerda/Phoenix/archive/master.zip","type":"tool","license":{"short":"cc-by-nd"}}
]
```

### Phoenix::load_settings()
Loads the settings from [phoenix.json](./phoenix.json) into the Phoenix-object.

### Phoenix::save_settings()
Saves the settings to [phoenix.json](./phoenix.json).

### Phoenix::clean_settings()
Removes additional data to provide an usable [phoenix.json](./phoenix.json) to do a fresh install on an other system.

### PHOENIX_FRAMEWORK
Phoenix is designed to work with [Hades](https://github.com/sentfanwyaerda/Hades). But if needed can be integrated into an other logical domain. By default it is set to `FALSE` and does not use a framework at all.

### Phoenix::get_framework_root($type)

