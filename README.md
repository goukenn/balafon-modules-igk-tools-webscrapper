# igk/tools/webscrapper

@C.A.D.BONDJEDOUE

Balafon's module use to get web content.

```
balafon --tools:webscrap url [--controller:controller_name]
```

Usage 
- create a WebScapperDocument
- parse html content 
- export to the output folder 

```php
$parser = new WebScrapperDocument;
if ($parser->parseContent($content)){
    $parser->exportTo($outDir);
}
```

we can setup the base Url with the `base` property;
```php
$parser->base = 'https://localhost';
```

