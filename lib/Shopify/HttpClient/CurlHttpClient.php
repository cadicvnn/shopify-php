<?php

namespace Shopify\HttpClient;

class CurlHttpClient extends HttpClientAdapter
{

    /**
     * set to false to stop cURL from verifying the peer's certificate
     * @var boolean
     */
    protected $verifyPeer = true;

    /**
     * set to 1 to check the existence of a common name in the SSL peer
     * certificate
     * set to 2 to check the existence of a common name and also verify
     * that it matches the hostname provided.
     * In production environments the value of this option should
     * be kept at 2 (default value).
     * @var integer
     */
    protected $verifyHost = 2;

    /**
     * The name of a file holding one or more certificates to verify
     * the peer with. This only makes sense when used in combination
     * with CURLOPT_SSL_VERIFYPEER
     * @var string
     */
    protected $certificatePath;

    /**
     * an array of headers to be used in the request
     * @var array
     */
    protected $headers;

    /**
     *
     *
     * @param string $certificatePath
     */
    public function __construct($certificatePath = null)
    {

        $this->certificatePath = $certificatePath;
        $this->headers = array();

    }

    /**
     * update the verify peer property
     * @param boolean $value
     */
    public function setVerifyPeer($value)
    {

        $this->verifyPeer = (bool) $value;

    }

    /**
     * update the value for the verify host property
     * @param boolean $value
     */
    public function setVerifyHost($value)
    {

        $this->verifyHost = $value;

    }

    public function get($uri, array $params = array())
    {

        $uri .= '?' . http_build_query($params);

        $ch = $this->initCurlHandler($uri);
        return $this->makeRequest($ch);

    }

    public function post($uri, $params = null)
    {

        $ch = $this->initCurlHandler($uri);
        curl_setopt($ch, CURLOPT_POST, true);

        if (!is_null($params) && !is_array($params)) {
            $this->headers[] = 'Content-Type: application/json';
        }

        if (!is_null($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        return $this->makeRequest($ch);

    }
    
    public function put($uri, $params = null)
    {
    
        $ch = $this->initCurlHandler($uri);
	curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    
        if (!is_null($params) && !is_array($params)) {
            $this->headers[] = 'Content-Type: application/json';
        }
    
        if (!is_null($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
    
        return $this->makeRequest($ch);
    
    }
    
    public function delete($uri, $params = null)
    {
    
        $ch = $this->initCurlHandler($uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    
        if (!is_null($params) && !is_array($params)) {
            $this->headers[] = 'Content-Type: application/json';
        }
    
        if (!is_null($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
    
        return $this->makeRequest($ch);
    
    }

    /**
     * initialize the cURL handler
     * @param string $uri
     * @return resource
     */
    protected function initCurlHandler($uri)
    {

        $this->headers = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_USERAGENT, 'cadicvnn/shopify-php client');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifyHost);

        if ($this->getAccessToken()) {
            $this->headers[] = self::SHOPIFY_ACCESS_TOKEN_HEADER
                . ": " . $this->getAccessToken();
        }

        if ($this->verifyPeer === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        } else {

            // @see http://curl.haxx.se/docs/caextract.html

            if (!file_exists($this->certificatePath)) {
                throw new \RuntimeException('cacert.pem file not found');
            }

            curl_setopt ($ch, CURLOPT_CAINFO, $this->certificatePath);

        }

        return $ch;

    }

    /**
     * make the cURL request
     * @param resource $ch
     * @return mixed
     */
    protected function makeRequest($ch)
    {

        if (count($this->headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);

        // Get http code from response
        $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $retry = 0;

        // Retry 6 times for preventing call limit API
        while (($responseHttpCode === CURLE_COULDNT_RESOLVE_HOST || $responseHttpCode === 429) && $retry < 6) {
            sleep(1 + $retry);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $responseHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $retry++;
        }

        if ($error) {
            curl_close($ch);
            throw new \RuntimeException($error, $responseHttpCode);
        }

        curl_close($ch);
        return $response;

    }
}
