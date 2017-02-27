# Phoenix
*An update helper*

"a phoenix (..) is a long-lived bird that is cyclically regenerated or reborn." &mdash; [Wikipedia](http://en.wikipedia.org/wiki/Phoenix_%28mythology%29)

```php
$mount = "/www/your/tools-directory/$archive/";
$src = "http://the.location.wh/ere/you/found/the/$archive.zip";
$P = new Phoenix($mount=NULL, $src=FALSE, $create=FALSE);
//To install the $archive:
$P->install($P->download($src));
//next time:
$P->update();
```

It is easy to synchronize archives with Phoenix. You should use an [phoenix.json](./phoenix.json)-file, to configure, like:
```json
[
{"name" : "$archive",
"mount": "/www/your/tools-directory/$archive/",
"src": "http://the.location.wh/ere/you/found/the/$archive.zip"}
]
```

As you might notice `$mount` is an absolute path. To be more flexible and compliant to the framework (like [Hades](https://github.com/sentfanwyaerda/Hades/)), you could assign `$type` as `*tool*`, or any other hotkey.

When `$src` is within the [GitHub](https://github.com/)-domain, the link can be analysed to provide more options. Try `Phoenix::get_github_data($src);`.


### Phoenix workflow
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