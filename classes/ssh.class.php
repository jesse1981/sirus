<?php
class ssh {
    // SSH Host
    private $ssh_host = SSH_HOST;
    // SSH Port
    private $ssh_port = SSH_PORT;
    // SSH Server Fingerprint
    private $ssh_server_fp = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    // SSH Username
    private $ssh_auth_user = SSH_USER;
    // SSH Public Key File
    private $ssh_auth_pub = '/home/username/.ssh/id_rsa.pub';
    // SSH Private Key File
    private $ssh_auth_priv = '/home/username/.ssh/id_rsa';
    // SSH Private Key Passphrase (null == no passphrase)
    private $ssh_auth_pass = SSH_PASS;
    // SSH Connection
    private $connection;
    
    public function __construct($server = SSH_HOST, $user="", $pass="") {
        if ($user) $this->ssh_auth_user = $user;
        if ($pass) $this->ssh_auth_pass = $pass;
        $this->connect($server);
    }
    public function __destruct() {
        $this->disconnect();
    }
   
    // public functions
    public function connect($server) {
        if (!($this->connection = ssh2_connect($server, $this->ssh_port))) {
            throw new Exception('Cannot connect to server');
        }
        if (!(ssh2_auth_password($this->connection, $this->ssh_auth_user, $this->ssh_auth_pass))) {
            $fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
            if (strcmp($this->ssh_server_fp, $fingerprint) !== 0) {
                throw new Exception('Unable to verify server identity!');
            }
            if (!ssh2_auth_pubkey_file($this->connection, $this->ssh_auth_user, $this->ssh_auth_pub, $this->ssh_auth_priv, $this->ssh_auth_pass)) {
                throw new Exception('Autentication rejected by server');
            }
        }
    }
    public function exec($cmd) {
        try {
            if (!($stream = ssh2_exec($this->connection, $cmd))) {
                throw new Exception('SSH command failed');
            }
            stream_set_blocking($stream, true);
            $data = "";
            while ($buf = fread($stream, 4096)) {
                $data .= $buf;
            }
            fclose($stream);
            return $data;
        }
        catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
    public function disconnect() {
        $this->exec('echo "EXITING" && exit;');
        $this->connection = null;
    }
    
    public function downloadFile($source,$dest) {
        if ($this->connection) {
            $result = ssh2_scp_recv($this->connection,$source,$dest);
            return $result;
        }
        else return false;
    }
    public function uploadFile($source,$dest) {
        if ($this->connection) {
            $result = ssh2_scp_send($this->connection,$source,$dest);
            return $result;
        }
    }
}
?> 
