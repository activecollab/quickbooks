#Quickbooks


## Usage

### Authentication

    $authenticator = \ActiveCollab\Quickbooks\Quickbooks::getAuthenticator([
        'consumerKey'       => 'example-consumer-key',
        'consumerKeySecret' => 'example-consumer-key-secret',
        'callbackUrl'       => 'http://example.com'
    ]);
    
    
### Querying API
    
    $dataService = \ActiveCollab\Quickbooks\Quickbooks::getDataService([
        'consumerKey'       => 'example-consumer-key',
        'consumerKeySecret' => 'example-consumer-key-secret',
        'accessToken'       => 'example-access-token',
        'accessTokenSecret' => 'example-access-token-secret',
        'callbackUrl'       => 'http://example.com',
        'realmId'           => 123456789
    ]);
