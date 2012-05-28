<?php


# Simple PHP http dumb proxy, using cURL
# Fixed to port 80 (CURLOPT_PORT seems not to have an effect?)

define ('REMOTE_SCV_HOST', 'http://piratalondon.com');   #no trailing slash

#set up http auth if needed
$use_http_auth = FALSE;
$auth_name = 'my_username';
$auth_pass = 'my_password';

# other options
define ('OPTIONS_TIMEOUT',5);
define ('OPTIONS_MAXREDIRECTS',5);


class Proxy {


    public function __construct($use_http_auth=FALSE,$auth_name=NULL,$auth_pass=NULL){
        
        # store args
        $this->use_http_auth = $use_http_auth;
        $this->auth_name = $auth_name;
        $this->auth_pass = $auth_pass;
        
        # init
        $remoteUri = $this->_getRemoteUri();        
        $response = $this->_fetchRemote($remoteUri);
        $this->_renderOutput($response);
    }
    
    
    private function _getRemoteUri(){
        
        $uri = $_SERVER['REQUEST_URI'];
        if(substr($uri, 0, 1) == '/')
            $uri = substr($uri, 1);
        
        return REMOTE_SCV_HOST.'/'.$uri;
    }
    
    
    private function _fetchRemote($url){

        if (!extension_loaded('curl')) {
            exit("curl extension not loaded");
        }

        $defaults = array( 
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 1, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => OPTIONS_TIMEOUT,
            CURLOPT_MAXCONNECTS => OPTIONS_MAXREDIRECTS,
        );

        #use password if option set
        if($this->use_http_auth){
            $defaults[CURLOPT_USERPWD] = $this->auth_name.':'.$this->auth_pass;
        }


        #cURL req.
        $ch = curl_init(); 
        curl_setopt_array($ch, $defaults); 
        if( ! $buffer = curl_exec($ch)){ 
            trigger_error(curl_error($ch)); 
        }

        # separate header from body
        $buffer = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);
        $header_size = $curl_info["header_size"];
        $response->headers = substr($buffer, 0, $header_size);
        $response->body = substr($buffer, $header_size);

        return $response; 

    }

    private function _renderOutput($response){

        # render page
        $headers = explode("\n",$response->headers);
        foreach($headers as $header){
            if(trim($header) != ''){
                header($header);
            }
        }

        echo $response->body;
    }
}


# run
$proxy = new Proxy($use_http_auth,$auth_name,$auth_pass);
