<?php
class RestUtils
{
    public static function processRequest()
    {
        // get our verb
        $requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $returnObj     = new RestRequest();
        // we'll store our data here
        $data          = array();
        
        switch (strtolower($requestMethod)) {
            case 'get':
                $data = $_GET;
                break;
            case 'post':
                $data = $_POST;
                break;
        } //strtolower($requestMethod)
        
        // store the method
        $returnObj->setMethod(strtolower($requestMethod));
        
        //setting the call name
        if (isset($_GET['call']) && trim($_GET['call']) != '') {
            $returnObj->setCall($_GET['call']);
        } //isset($_GET['call']) && trim($_GET['call']) != ''
        
        // set the raw data, so we can access it if needed (there may be
        // other pieces to your requests)
        $returnObj->setData($data);
        
        return $returnObj;
    }
    
    public static function sendBadResponse($message, $code = null)
    {
        header('Content-type: application/json');
        $a = array(
            'success' => false,
            'message' => $message
        );
        if($code) $a['code'] = $code;
        $responseJson = json_encode($a);
        die($responseJson);
        exit;
    }
    
    public static function sendGoodResponse($message, $data = false)
    {
        header('Content-type: application/json');
        if (!$data)
            $data = array();
        $data['success'] = true;
        $data['message'] = $message;
        $responseJson    = json_encode($data);
        die($responseJson);
        exit;
    }
    
    public static function dieFailureJsonResponse($message)
    {
        die(json_encode(Array(
            'success' => false,
            'message' => $message
        )));
    }
    
    public static function dieSuccessJsonResponse($data)
    {
        $data['success'] = true;
        die(json_encode($data));
    }
}

class RestRequest
{
    private $call;
    private $data;
    private $http_accept;
    private $method;
    
    public function __construct()
    {
        $this->call        = null;
        $this->data        = array();
        $this->http_accept = 'application/json';
        $this->method      = '';
    }
    
    public function setCall($call)
    {
        $this->call = strtolower($call);
    }
    
    public function setData($data)
    {
        $this->data = $data;
    }
    
    public function setMethod($method)
    {
        $this->method = $method;
    }
    
    public function getCall()
    {
        return $this->call;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getMethod()
    {
        return $this->method;
    }
    
    public function getHttpAccept()
    {
        return $this->http_accept;
    }
}
?>