<?php
class profile extends template {
    public function index() {
        $this->setView('profile','_master.php');
    }
    public function saveAvatar() {
        if ((isset($_FILES["avatar"])) && ($_FILES["avatar"]["error"] == UPLOAD_ERR_OK)) {
            // Uploaded
            $tmp_name = $_FILES["avatar"]["tmp_name"];
            $name = $_FILES["avatar"]["name"];
            $filename = UPLOADS."$name";
            move_uploaded_file($tmp_name, $filename);
            // Ok? Move to user ID folder
            $session = new session();
            $userid = $session->getKey('user_id');
            $dest = "/var/www/img/avatar/$userid";
            if (!file_exists($dest)) {
                mkdir($dest,0777,true);
            }
            copy($filename, "$dest/$name");
            // Set to the session
            $dest = "/img/avatar/$userid/$name";
            $session->addKey('avatar',$dest);
            // Save it to the user
            $db = new database();
            $sql = "UPDATE users SET avatar='$dest' WHERE id=$userid";
            $db->query($sql);
            // Now redirect
            $dash = new dashboard();
            $dash->redirect('profile');
        }
    }
    public function saveDetails() {
        $sess = new session();
        $dash = new dashboard();
        $data = new database();
        $post = $dash->getPost();
        $user = $sess->getKey('user_id');
        
        $firstname  = $post["firstname"];
        $lastname   = $post["lastname"];
        $department = $post["department"];
        $email      = $post["email"];
        
        $sql = "UPDATE users SET firstname='$firstname', lastname='$lastname', department='$department', email='$email' WHERE id=$user";
        $res = $data->query($sql);
        
        $sess->addKey('firstname', $firstname);
        $sess->addKey('lastname', $lastname);
        $sess->addKey('department', $department);
        $sess->addKey('email', $email);
        
        $dash->redirect('profile');
    }
}
?>