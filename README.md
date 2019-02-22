# PHP-Pexels

Simple, easy-to-use Pexels PHP SDK for making requests (including paginated recursive requests) against the Pexels API. Includes more advanced (than usual) features including handling failed requests (re-requesting one additional time, incase there was an SSL or connection handshake error) along with making sequential requests when the desired limit exceeds the Pexels API limit.

### Sample Usage
``` php
$client = new Pexels('abcdef_0000');
$client->setOffset(0);
$client->setLimit(10);
$response = $client->search('nature');
print_r($response);
exit(0);
```
