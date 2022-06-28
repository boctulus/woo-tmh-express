<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\WooTMHExpress\libs;

class Request
{
    static protected $query_arr;
    static protected $raw;
    static protected $body;
    static protected $params;
    static protected $headers;
    static protected $accept_encoding;
    static protected $instance = NULL;

    protected function __construct() { }

    static function getInstance(){
        if(static::$instance == NULL){
            if (php_sapi_name() != 'cli'){
                if (isset($_SERVER['QUERY_STRING'])){
					static::$query_arr = Url::queryString();

                    if (isset(static::$query_arr["accept_encodig"])){
                        static::$accept_encoding = static::$query_arr["accept_encodig"];
                        unset(static::$query_arr["accept_encodig"]);
                    }
				}
                
                static::$raw  = file_get_contents("php://input");
                static::$body = json_decode(static::$raw, true);
                static::$headers = apache_request_headers();

                $tmp = [];
                foreach (static::$headers as $key => $val){
                    $tmp[strtolower($key)] = $val;
                }
                static::$headers = $tmp;
                
            }
            static::$instance = new static();
        }
        
        return static::$instance;
    }
    
    function setParams($params){
        static::$params = $params;
        return static::getInstance();
    }

    function headers(){
        return static::$headers;
    }

    function header(string $key){
        return static::$headers[strtolower($key)] ?? NULL;
    }

    // alias
    function getHeader(string $key){
        return $this->header($key);
    }

    function shiftHeader(string $key){
        $key = strtolower($key);

        $out = static::$headers[$key] ?? null;
        unset(static::$headers[$key]);

        return $out;
    }

    function getAuth(){
        return static::$headers['authorization'] ?? NULL;
    }

    function hasAuth(){
        return $this->getAuth() != NULL; 
    }

    function getApiKey(){
        return  static::$headers['x-api-key'] ?? 
                $this->shiftQuery('api_key') ??                
                NULL;
    }

    function hasApiKey(){
        return $this->getApiKey() != NULL; 
    }

    function getTenantId(){
        return  
            $this->shiftQuery('tenantid') ??
            static::$headers['x-tenant-id'] ??             
            NULL;
    }

    function hasTenantId(){
        return $this->getTenantId() !== NULL; 
    }

    function authMethod(){
        if ($this->hasApiKey()){
            return 'API_KEY';
        }elseif ($this->hasAuth()){
            return 'JWT';
        }
    }

    /*  
        https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Encoding
    */
    function acceptEncoding() : ?string {
        if (static::$accept_encoding){
            return static::$accept_encoding;
        }

        return static::shiftHeader('Accept-Encoding');
    }

    function gzip(){
        return in_array('gzip', explode(',', static::acceptEncoding() ?? ''));
    }

    function deflate(){
        return in_array('deflate', explode(',', static::acceptEncoding() ?? ''));
    }

    function getQuery(string $key = null)
    {
        if ($key == null)
            return static::$query_arr;
        else 
             return static::$query_arr[$key];   
    }    

    // getter destructivo sobre $query_arr
    function shiftQuery($key, $default_value = NULL)
    {
        static $arr = [];

        if (isset($arr[$key])){
            return $arr[$key];
        }

        if (isset(static::$query_arr[$key])){
            $out = static::$query_arr[$key];
            unset(static::$query_arr[$key]);
            $arr[$key] = $out;
        } else {
            $out = $default_value;
        }

        return $out;
    }

    function getParam($index){
        return static::$params[$index];
    } 

    function getParams(){
        return static::$params;
    } 

    function getBody($as_obj = true)
    {
        return $as_obj ? (object) static::$body : static::$body;
    }

    function getBodyParam($key){
        return static::$body[$key] ?? NULL;
    }

    // getter destructivo sobre el body
    function shiftBodyParam($key){
        if (!isset(static::$body[$key])){
            return NULL;
        }

        $ret = static::$body[$key];

        unset(static::$body[$key]);
        return $ret;
    }

    function getCode(){
        return http_response_code();
    }

    static function ip(){
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    }

    static function user_agent(){
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /* Arrayable Interface */ 

    function toArray(){
        return static::$params;
    }

}