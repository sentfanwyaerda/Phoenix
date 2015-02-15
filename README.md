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

It is easy to make an archive keep working with Phoenix. You need to make an [phoenix.json](./phoenix.json)-file:
```json
{"name" : "$archive",
"src": "http://the.location.wh/ere/you/found/the/$archive.zip"
}
```