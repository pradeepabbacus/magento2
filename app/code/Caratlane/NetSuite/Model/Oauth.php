<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2020 18th DigiTech (https://www.18thdigitech.com)
 * @package Caratlane_NetSuite
 */
namespace Caratlane\NetSuite\Model;

use Magento\Store\Model\ScopeInterface;

class Oauth
{
    const SIGNATURE_METHOD_HMACSHA256 = 'HMAC-SHA256';
    const OAUTH_SCRIPT = 'netsuite/apidetail/script';//'694';
    const OAUTH_VERSION = '1.0';
    const NONCE_STRING = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const XML_PATH_OAUTH_REALM   = 'netsuite/apidetail/oauth_realm';
    const XML_PATH_OAUTH_CONSUMER_KEY   = 'netsuite/apidetail/oauth_consumer_key';
    const XML_PATH_OAUTH_CONSUMER_SECRET   = 'netsuite/apidetail/oauth_consumer_secret';
    const XML_PATH_OAUTH_TOKEN   = 'netsuite/apidetail/oauth_token';
    const XML_PATH_OAUTH_TOKEN_SECRET   = 'netsuite/apidetail/oauth_token_secret';
    const XML_PATH_OAUTH_URL   = 'netsuite/cronScheduled/api_url';
    
    /**
     * @var storeManager
     */
     protected $storeManager;
     
     /**
      * @var scopeConfig
      */
     protected $scopeConfig;
     
     /**
      * @param \Magento\Store\Model\StoreManagerInterface $storeManager
      * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
      **/
     
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }
    
    /*
     * consumer key
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->getConfigValue(self::XML_PATH_OAUTH_CONSUMER_KEY);
    }
    
    /*
     * consumer Secret
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->getConfigValue(self::XML_PATH_OAUTH_CONSUMER_SECRET);
    }
    
    /*
     * Token access key
     * @return string
     */
    public function getToken()
    {
        return $this->getConfigValue(self::XML_PATH_OAUTH_TOKEN);
    }
    
    /*
     * Token Secret
     * @return string
     */
    public function getTokenSecret()
    {
        return $this->getConfigValue(self::XML_PATH_OAUTH_TOKEN_SECRET);
    }
    
    /*
     * Token Secreat
     * @return string
     */
    public function getRealm()
    {
        return $this->getConfigValue(self::XML_PATH_OAUTH_REALM);
    }
    /*
     * Token script
     * @return string
     */
    public function getScript()
    {
        return $this->getConfigValue(self::OAUTH_SCRIPT);
    }
    /*
     * Token Secreat
     * @return  string
     */
    public function getApiUrl()
    {
        return $this->getConfigValue(self::XML_PATH_OAUTH_URL);
    }
    /*
     * Calculate header for request
     * @return string
     */
    public function getOauthParams()
    {
        $time= time();
        $nonce = rawurlencode($this->generateNonceString(11));
        $oauthparams =  $this->getParamObject($nonce, $time);
        $baseString = $this->createBaseString($oauthparams);
        $signature = $this->getSignature('sha256', $baseString);
        return $headerValue = $this->buildAuthorizationHeader($nonce, $time, $signature);
    }
    
    /*
     * Calculate signature for request
     * @return string
     */
    public function getSignature($algo, $baseString)
    {
         $key = rawurlencode($this->getConsumerSecret()) . '&' . rawurlencode($this->getTokenSecret());
          return rawurlencode(base64_encode(hash_hmac($algo, $baseString, $key, true)));
    }
    
    /**
     * Builds the Authorization header for a request
     * @return array
     */
    private function buildAuthorizationHeader($nonce, $time, $signature)
    {
        return 'OAuth realm="'.$this->getRealm().'", oauth_consumer_key="'.$this->getConsumerKey().'",oauth_nonce="'.$nonce.'",oauth_signature_method="HMAC-SHA256",oauth_timestamp="'.$time.'",oauth_token="'.$this->getToken().'",oauth_version="1.0",oauth_signature="'.$signature.'"';
    }
    
     /**
      * Creates the Signature Base String.
      * The Signature Base String is a consistent reproducible concatenation of
      * the request elements into a single string. The string is used as an
      * input in hashing or signing algorithms.
      * @return string Returns the base string
      */
     
    private function createBaseString($params)
    {
        $url = $this->getApiUrl();
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return strtoupper('POST')
            . '&' . rawurlencode($url)
            . '&' . rawurlencode($query);
    }
    /*
     * generating nonce value
     * @return string
    */
    public function generateNonceString($length = 11)
    {
        $characters = self::NONCE_STRING;
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    /*
     * generating param value
     * @return array
    */
    public function getParamObject($nonce, $time)
    {
        return [
                'deploy'=>1,
                "oauth_consumer_key"=> $this->getConsumerKey(),
                "oauth_nonce"=> $nonce,
                "oauth_signature_method"=>self::SIGNATURE_METHOD_HMACSHA256,
                "oauth_timestamp"=> $time,
                "oauth_token"=>$this->getToken(),
                "oauth_version"=> self::OAUTH_VERSION,
                'script'=>  $this->getScript()
            ];
    }
    
    /*
     * get config value
     * return string
     */
    public function getConfigValue($path)
    {
        $store = $this->storeManager->getStore()->getStoreId();
        $config_data = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
        return $config_data;
    }
}
