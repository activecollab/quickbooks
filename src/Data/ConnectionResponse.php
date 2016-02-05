<?php

namespace ActiveCollab\Quickbooks\Data;

class ConnectionResponse
{

    /**
     * @var int
     */
    protected $error_code;

    /**
     * @var string
     */
    protected $error_message;

    /**
     * @var string
     */
    protected $oauth_token;

    /**
     * @var string
     */
    protected $oauth_token_secret;

    /**
     * Construct entity
     * 
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->error_code = isset($data['ErrorCode']) ? $data['ErrorCode'] : 0;
        $this->error_message = isset($data['ErrorMessage']) ? $data['ErrorMessage'] : '';
        $this->oauth_token = isset($data['OAuthToken']) ? $data['OAuthToken'] : null;
        $this->oauth_token_secret = isset($data['OAuthTokenSecret']) ? $data['OAuthTokenSecret'] : null;
    }

    /**
     * Return error code
     * 
     * @return int
     */
    public function getErrorCode()
    {
        return intval($this->error_code);
    }

    /**
     * Return error message
     * 
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }

    /**
     * Return oauth token
     * 
     * @return string
     */
    public function getOAuthToken()
    {
        return $this->oauth_token;
    }

    /**
     * Return oauth secret
     * 
     * @return string
     */
    public function getOAuthTokenSecret()
    {
        return $this->oauth_token_secret;
    }
}