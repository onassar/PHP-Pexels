# PHP-Pexels

Simple, easy-to-use Pexels PHP SDK for making requests (including paginated recursive requests) against the Pexels API.

### Sample Usage
``` php
$client = new Pexels('abcdef_0000');
$client->setOffset(0);
$client->setLimit(10);
$response = $client->search('nature');
print_r($response);
exit(0);
```
