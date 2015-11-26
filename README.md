#Quickbooks


## Usage

### Authentication

```php
$quickbooks = new ActiveCollab\Quickbooks\Quickbooks([
    'identifier'    => 'example-consumer-key',
    'secret'        => 'example-consumer-key-secret',
    'callback_uri'  => 'http://example.com'
]);
```

### Sandbox

```php
$sandbox = new ActiveCollab\Quickbooks\Sandbox(
    'example-consumer-key',
    'example-consumer-key-secret',
    'example-access-token',
    'example-access-token-secret',
    'example-realmId'
);
```
    
### Querying API
   
```php
$dataService = new ActiveCollab\Quickbooks\DataService(
    'example-consumer-key',
    'example-consumer-key-secret',
    'example-access-token',
    'example-access-token-secret',
    'example-realmId'
);
```

