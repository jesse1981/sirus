<?php
class keycapture extends template {
    var $sessions = array();
    
    public function event() {
        $dash = new dashboard();
        $post = $dash->getPost();
        
        $code       = $post["code"];
        $session    = $post["session"];
        $ontroller  = $post["controller"];
        $field      = $post["field"];
        
        
    }
}
?>