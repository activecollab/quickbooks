#Quickbooks


## Usage

### Authentication

    $quickbooks = new ActiveCollab\Quickbooks\Quickbooks([
        'identifier'    => 'example-consumer-key',
        'secret'        => 'example-consumer-key-secret',
        'callback_uri'  => 'http://example.com'
    ]);
    
    
### Querying API
    
    $dataService = new ActiveCollab\Quickbooks\DataService(
        'example-consumer-key',
        'example-consumer-key-secret',
        'example-access-token',
        'example-access-token-secret',
        'example-realmId'
    );
