<?php

namespace ActiveCollab\Quickbooks;


class Config 
{
    const API_VERSION = 3;

    const URL_API           = 'https://sandbox-quickbooks.api.intuit.com';
    const URL_REQUEST_TOKEN = 'https://oauth.intuit.com/oauth/v1/get_request_token';
    const URL_AUTHORIZATION = 'https://appcenter.intuit.com/Connect/Begin';
    const URL_ACCESS_TOKEN  = 'https://oauth.intuit.com/oauth/v1/get_access_token';
}