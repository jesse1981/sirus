<?php
class zip {
    
    var $local;
    var $status;
    
    public function __construct($filename,$open = false,$overwrite = false) {
        $this->local = new ZipArchive;
        $this->createOrOpen($filename, $open, $overwrite);
    }
    public function __destruct() {
        // Close any existing
        $this->close();
    }
    private function friendlyError($res) {
        switch ($res) {
            case ZipArchive::ER_EXISTS:
                $res = "File already exists.";
                break;
            case ZipArchive::ER_INCONS:
                $res = "Zip archive inconsistent.";
                break;
            case ZipArchive::ER_INVAL:
                $res = "Invalid argument.";
                break;
            case ZipArchive::ER_MEMORY:
                $res = "Malloc failure.";
                break;
            case ZipArchive::ER_NOENT:
                $res = "No such file.";
                break;
            case ZipArchive::ER_NOZIP:
                $res = "Not a zip archive.";
                break;
            case ZipArchive::ER_OPEN:
                $res = "Can't open file.";
                break;
            case ZipArchive::ER_READ:
                $res = "Read Error.";
                break;
            case ZipArchive::ER_SEEK:
                $res = "Seek error.";
                break;
        }
        return $res;
    }
    
    public function createOrOpen($filename,$open = false,$overwrite = false) {
        
        // Try to create
        if (($filename)     && (!$open))        $this->status = $this->local->open($filename, ZIPARCHIVE::CREATE);
        elseif (($filename) && ($overwrite))    $this->status = $this->local->open($filename, ZIPARCHIVE::OVERWRITE);
        elseif (($filename) && ($open))         $this->status = $this->local->open($filename);

        if ($this->status!==true) $this->status = $this->friendlyError($this->status);
    }
    public function close() {
        if ($this->local===true) return $this->local->close();
        else return false;
    }
    public function getStatus() {
        return $this->status;
    }
    
    public function addData($localname,$data) {
        // Return true/false
        if ($this->local===true) return $this->local->addFromString($localname,$data);
        else return false;
    }
    public function addDir($dir) {
        // Return true/false
        if ($this->local===true) return $this->local->addEmptyDir($dir);
        else return false;
    }
    public function addFile($file,$localname) {
        // Add file (return true/false
        if ($this->local===true) return $this->local->addFile($file, $localname);
        else return false;
    }
    public function remove($name) {
        // Add file (return true/false
        if ($this->local===true) return $this->local->deleteName($name);
        else return false;
    }
    public function extract($dest,$entries = array()) {
        // Return true/false
        if ($this->status===true) {
            if (count($entries)) return $this->local->extractTo($dest,$entries);
            else return $this->local->extractTo($dest);
        }
        else return false;
    }
    
    public function getComment() {
        // Return true/false
        if ($this->local===true) return $this->local->getArchiveComment();
        else return false;
    }
    public function getData($name) {
        // Return true/false
        if ($this->local===true) return $this->local->getFromName($name);
        else return false;
    }
    public function getErrorDescription($code) {
        switch ($code) {
            case ZIPARCHIVE::ER_EXISTS:
                return "File already exists.";
                break;
            case ZIPARCHIVE::ER_INCONS:
                return "Zip archive inconsistent.";
                break;
            case ZIPARCHIVE::ER_INVAL:
                return "Invalid argument.";
                break;
            case ZIPARCHIVE::ER_MEMORY:
                return "Malloc failure.";
                break;
            case ZIPARCHIVE::ER_NOENT:
                return "No such file.";
                break;
            case ZIPARCHIVE::ER_NOZIP:
                return "Not a zip archive.";
                break;
            case ZIPARCHIVE::ER_OPEN:
                return "Can't open file.";
                break;
            case ZIPARCHIVE::ER_READ:
                return "Read error.";
                break;
            case ZIPARCHIVE::ER_SEEK:
                return "Seek error.";
                break;
            default:
                return "Unkown error code.";
        }

    }
    public function getLastStatus() {
        // return msg/false
        if ($this->local===true) return $this->local->getStatusString();
        else return false;
    }
    public function setComment($comment) {
        // Return true/false
        if ($this->local===true) return $this->local->setArchiveComment($comment);
        else return false;
    }
}
?>