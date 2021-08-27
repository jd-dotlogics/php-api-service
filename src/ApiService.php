<?php

namespace Dotlogics;

use GuzzleHttp\Client;

/**
 * A helper class for sending HTTP requiest by using GuzzleHttp
 */
class ApiService
{
    const HTTP_METHOD_GET = 'get';
    const HTTP_METHOD_POST = 'post';
    const HTTP_METHOD_PUT = 'put';
    const HTTP_METHOD_DELETE = 'delete';

    protected $defaultOptopns = [];
    protected $client = null;
    protected $baseUri = null;


    /**
     * Construct the service with defualt values
     * 
     * @param string? $base_uri
     * @param array $headers
     */
    function __construct($base_uri = null, $headers = [])
    {
        $this->baseUri = $base_uri;

        $this->defaultOptopns = [
            'method' => self::HTTP_METHOD_GET,
            'query' => [],
            'data' => [],
            'headers' => $headers,
            'raw_response' => false,
        ];
    }

    /**
     * Call before each HTTP request
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $query
     * @param array $data
     * @param array $headers
     */
    protected function preCallHook($method, $endpoint, $query, &$data, &$headers)
    {
        # update request data
    }

    /**
     * Get HTTP client
     * 
     * @param bool $re_initiate
     * 
     * @return GuzzleHttp\Client
     */
    public function getHttpClient($re_initiate = false)
    {
        if($re_initiate || !$this->client){
            $options = [];

            if(!empty($this->baseUri)){
                $options['base_uri'] = $this->baseUri;
            }

            $this->client = new Client($options); 
        }

        return $this->client;
    }

    /**
     * Send a HTTP request
     * 
     * @param string $endpoint
     * @param array $options
     * 
     * @return array|object|null
     */
    public function call($endpoint, $options = [])
    {
        extract(array_merge($this->defaultOptopns, $options));
    
        $client = $this->getHttpClient();
        try {
            $this->preCallHook($method, $endpoint, $query, $data, $headers);

            $req_options = [
                'query' => $query,                
                'headers' => $headers,
                // 'debug' => fopen('php://stderr', 'w'),
            ];

            if(strtolower($method) != 'get'){
                $req_options['json'] = $data;
            }

            // dd($method, $endpoint, $req_options);
            $response = $client->request($method, $endpoint, $req_options);
    
            $success = true;
        } catch (\Throwable $exception) {
            $response = method_exists($exception, 'getResponse') ? $exception->getResponse() : null;
            $success = false;
        }
    
        if($raw_response){
            return $response ?? $exception  ?? null;
        }
    
        if(!empty($response)){
            $status_code = $response->getStatusCode();
            $response_data = json_decode($content = (string) $response->getBody(), true);
    
            if(json_last_error() !== JSON_ERROR_NONE){
                $response_data = ['raw_content' => $content];
            }
        }else if(!empty($exception)){
            $status_code = $exception->getCode();
            $response_data = ['error' => $exception->getMessage()];
        }else{
            $status_code = 500;
            $response_data = ['error' => 'Internel Server Error!'];
        }
    
    
        return [
            'status' => $success,
            'status_code' => $status_code,
            'data' => $response_data,
        ];
    }

    /**
     * Send a HTTP request
     * Alias for call method with options as parameters instead of array
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $query
     * @param array $data
     * @param array $headers
     * @param bool $raw_response
     * 
     * @return array|object|null
     */
    public function proxyCall($method, $endpoint, $query = [], $data = [], $headers = [], $raw_response = false)
    {
        $options['query'] = $query;
        
        if(strtolower($method) != self::HTTP_METHOD_GET){
            $options['data'] = $data;
        }

        if(!empty($headers)){
            $options['headers'] = $headers; 
        }
        $options['raw_response'] = $raw_response; 
        $options['method'] = $method;
        
        return $this->call($endpoint, $options);
    }

    /**
     * Send a HTTP GET request
     * 
     * @param string $endpoint
     * @param array $query
     * @param array $headers
     * @param bool $raw_response
     * 
     * @return array|object|null
     */
    public function get($endpoint, $query = [], $headers = [], $raw_response = false)
    {
        return $this->proxyCall(self::HTTP_METHOD_GET, $endpoint, $query, [], $headers, $raw_response);
    }

    /**
     * Send a HTTP POST request
     * 
     * @param string $endpoint
     * @param array $data
     * @param array $query
     * @param array $headers
     * @param bool $raw_response
     * 
     * @return array|object|null
     */
    public function post($endpoint, $data = [], $query = [], $headers = [], $raw_response = false)
    {
        return $this->proxyCall(self::HTTP_METHOD_POST, $endpoint, $query, $data, $headers, $raw_response);
    }
    
    /**
     * Send a HTTP PUT request
     * 
     * @param string $endpoint
     * @param array $data
     * @param array $query
     * @param array $headers
     * @param bool $raw_response
     * 
     * @return array|object|null
     */
    public function put($endpoint, $data = [], $query = [], $headers = [], $raw_response = false)
    {
        return $this->proxyCall(self::HTTP_METHOD_PUT, $endpoint, $query, $data, $headers, $raw_response);
    }
    
    /**
     *  Send a HTTP DELETE request
     * 
     * @param string $endpoint
     * @param array $query
     * @param array $headers
     * @param bool $raw_response
     * 
     * @return array|object|null
     */
    public function delete($endpoint, $query = [], $headers = [], $raw_response = false)
    {
        return $this->proxyCall(self::HTTP_METHOD_DELETE, $endpoint, $query, [], $headers, $raw_response);
    }
}
