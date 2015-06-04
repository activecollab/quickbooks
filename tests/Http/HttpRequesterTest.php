<?php

class HttpClientTest extends TestCase
{
    public function testMakesApiRequestAndReturnArray()
    {
        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(200,null,$this->getFixture('invoices',false)));
        $mockedClient = new Guzzle\Http\Client();
        $mockedClient->addSubscriber($plugin);

        $requester = new \ActiveCollab\Quickbooks\Http\HttpRequester($mockedClient);
        $response = $requester->request('GET', 'http://mock.com',null,[]);

        $this->assertArray($response);
    }

    /** @expectedException \ActiveCollab\Quickbooks\Exception\ApiRequestException */
    public function testThrowExceptionWhenResponseCodeNot200()
    {
        $plugin = new Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(401,null,$this->getFixture('invoices',false)));
        $mockedClient = new Guzzle\Http\Client();
        $mockedClient->addSubscriber($plugin);

        $requester = new \ActiveCollab\Quickbooks\Http\HttpRequester($mockedClient);
        $requester->request('GET', 'http://mock.com',null,[]);
    }
}