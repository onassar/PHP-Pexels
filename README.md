# PHP-Pexels
PHP SDK for running queries against the millions of icons provided by
[Pexels](https://pexels.com). Includes recursive searches.

### Supports
- Searches

### Sample Search
``` php
$client = new onassar\Pexels\Pexels();
$client->setAPIKey('***');
$client->setLimit(10);
$client->setOffset(0);
$results = $client->search('love') ?? array();
print_r($results);
exit(0);
```

### Note
Requires
[PHP-RemoteRequests](https://github.com/onassar/PHP-RemoteRequests).
