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
2. If installed; checks if current files has been changed since the last update, and when testing-functionality is available, it tests the functionality of the current install and saves the results.
3. Identifies the target by `$src`. If it is on [Github](http://github.com/) it analyses the current version: gets releases and branches and statistics (issues, forks, stargazers), and data of the current commit (sha, comment, author).
4. If installed; it determines if update is available and reports a notification.
5. Phoenix downloads the current release.
6. Phoenix compares the current release to the current install (or an previous to current), to determine custumizations. If custumized, it back-ups the actual installation, and requires a manual upgrade.
7. Phoenix updates to the current release. (optionally could this be automated)
8. Phoenix tests the (newly) current release and compares the results. If it fails on critical tests, it automatically rolls-back to the (previous) current install.
9. Phoenix reports a notification of success.

Currently only steps 3, 5, 7 of the workflow are available and test-worthy.

# Documentation

### Phoenix($phoenix_file, $auto=FALSE)
This function is a configuration short of the *auto upgrade* procedure.

### new Phoenix()

`$P = new Phoenix('./phoenix.json');` directly loads a specific `phoenix.json`-file.

`$P = new Phoenix($mount, $src, $force=FALSE);` creates one bare entry to process.

### Phoenix::install($archive)
*alias: `Phoenix::git_clone()`*

### Phoenix::upgrade($archive, $force=FALSE)
*alias: `Phoenix::git_pull()`*

### Phoenix::backup()

### Phoenix::stall()

### Phoenix::revert($to)
*alias: `Phoenix::restore()`*

### Phoenix::uninstall($mount)

### Phoenix::download()

### Phoenix::mtime_rollback($mount=NULL, $src=FALSE)
Sets the `mtime` back in time when an older matching and identical file exists (in an archive). This fixes overwritten files that in fact has not changed.

`$mount` should be a local directory. `$src` can be a directory or a zip-file.

### Phoenix::fingerprint($mount, $root=FALSE)
`$mount` can be a directory or a zip-file.

### Phoenix::fingerprint_diff($old=array(), $new=array(), $compare=0x0FF)
*alias: `Phoenix::fingerprint_compare()`*

`$old` and `$new` are suspected to be results of `Phoenix::fingerprint()`. It compares both.

### Phoenix::get_github_data($src)

### (bool) Phoenix::is_authenticated()
To provide a shell of security (e.g. test if the user is authenticated and has `administrator` rights), Phoenix uses [Heracles](https://github.com/sentfanwyaerda/Heracles/) or an valid *FRAMEWORK* for this task.

**WARNING**: when [Heracles](https://github.com/sentfanwyaerda/Heracles) or an valid *FRAMEWORK* is not available it will return the value of `PHOENIX_AUTHENTICATED` (default: `TRUE`).

### (bool) Phoenix::is_enabled()

### (bool) Phoenix::upgrade_available()

### (bool) Phoenix::git_enabled()

### PHOENIX_ARCHIVE
The constant `PHOENIX_ARCHIVE` sets the directory where all downloaded repositories are saved, and where the (overall) [phoenix.json](./phoenix.json) database is located. If you need to deviate from `dirname(dirname(__FILE__))`, make sure to set this constant before loading the Phoenix-library.

## Navigating the Phoenix database

`Phoenix::next()` `Phoenix::prev()`
`Phoenix::end()` `Phoenix::reset()` `Phoenix::current()`

### Phoenix::doAll(TRUE)
Sets the cursor to proces 'all' instead of a particular $index

### Phoenix::set_cursor($i)
Sets the cursor to a particular $index

### Phoenix::getIndexByName($archive)
Searches the settings for the matching entry.

### Phoenix::getMountByIndex($i)
Gives the static $mount or (if available) the $mount result based upon the framework.

### Phoenix::getVariableByIndex($i, 'src')
This method is an alias of `$P->settings[$i]['src']`.

## Handeling Settings

### [phoenix.json](./phoenix.json)
```json
[
   {
      "name":"Phoenix",
      "src":"https://github.com/sentfanwyaerda/Phoenix/archive/master.zip",
      "type":"tool",
      "license":{"short":"cc-by-nd"}
   }
]
```

Each entry could use $name, $src, $mount or $type, $license, and any other meta-data. After installing the version-data will be added: mtime, release, git:sha1.

### Phoenix::load_settings($file=FALSE)
Loads the settings from [phoenix.json](./phoenix.json) into the Phoenix-object.

### Phoenix::merge_settings($file, $overwrite=TRUE)
Loads additional settings from an other [phoenix.json](./phoenix.json)-file.

### Phoenix::save_settings($file=FALSE)
Saves the settings to [phoenix.json](./phoenix.json).

### Phoenix::clear_settings()
Removes additional data to provide an usable [phoenix.json](./phoenix.json) to do a fresh install on an other system.

## Handeling Buffer
Like *settings* it can handle a buffer with `Phoenix::load_buffer()`, `Phoenix::merge_buffer()`, `Phoenix::save_buffer()` and `Phoenix::clear_buffer()`.

## Integration with a framework

### PHOENIX_FRAMEWORK
Phoenix is designed to work with [Hades](https://github.com/sentfanwyaerda/Hades). But if needed can be integrated into an other logical domain. By default it is set to `FALSE` and does not use a framework at all.

### Phoenix::get_framework_root($type)
If available, it will give the result of:
```php
$framework = new PHOENIX_FRAMEWORK;
return $framework->get_root($type);
```

### PHOENIX_GITHUB_LIFESPAN
The time in seconds the data retrieved from GitHub should be kept valid within the buffer. Default is set to `3600` *(one hour)*.

### PHOENIX_GITLAB_DOMAIN

### PHOENIX_CHMOD
Define the default CHMOD by an octal value like: `0777`

## Dependencies and extensions
By design it only needs [PHP](https://www.php.net/), but it can use several other libraries to integrate more functionality.

### JSONplus
**Phoenix** could use **[JSONplus](https://github.com/sentfanwyaerda/JSONplus)** instead of the default `json_encode()` and `json_decode()` to provide some more human-readability and other features.

### Heracles
**Heracles** can provide the actual logic to validate `Phoenix::is_authenticated()`. If available, only authenticated user-accounts with `administrator`-priviledges have the right/functionality to use **Phoenix**. **Heracles** puts it on *lock down*. You should use **Heracles** or an *FRAMEWORK* that implements `->is_authenticated()` and (optionally) `->has_role()`.

### a Git-layer class
> to add integration after the next stable release (v2.0), because the library has not yet been chosen.