<?php
class dashboard {
    var $get;
    var $post;
    
    public function __construct() {
        
        $this->get  = $_GET;
        $this->post = $_POST;
        
        foreach ($this->get as $k=>$v) {
            $this->get[$k] = htmlentities($v);
        }
        foreach ($this->post as $k=>$v) {
            if (!is_array($v)) $this->post[$k] = htmlentities($v);
        }
    }
    
    public function getGet() {
        return $this->get;
    }
    public function getPost() {
        return $this->post;
    }
    public function _request($key) {
        if (isset($_REQUEST[$key])) return htmlentities ($_REQUEST[$key],ENT_QUOTES);
        else return false;
    }
    
    public function raiseEvent($obj,$event,$args) {
        // call routine
        $method = "action$event";
        $obj->$method($args);
    }
    public function getFieldValues($table,$fields) {
        $values = array();
        foreach ($fields as $item) {
            if ($this->_request($item->name)) $values[$table."_".$item->name] = $this->_request($item->name);
        }
        
        return $values;
    }
    
    public function redirect($url) {
        header('Location: '.ROOTWEB.$url);
    }

    public function sendPost($url,$fields) {
        $fields_string = "";
        
        foreach($fields as $key=>$value) $fields_string .= $key.'='.$value.'&';
        rtrim($fields_string, '&');
        
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        
        //execute post
        $result = curl_exec($ch);
        
        //close connection
        curl_close($ch);
        
        // return data
        return $result;
    }
}
?>