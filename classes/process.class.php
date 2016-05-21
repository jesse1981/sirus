<?php
class process {
    private $resource;
    private $cmd;
    private $pipes;
    private $start_time;
   
    public function __construct($cmd) {
        $this->script = $script;
        $this->max_execution_time = $max_execution_time;
        $descriptorspec    = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        $this->resource     = proc_open($this->cmd, $descriptorspec, $this->pipes, null, $_ENV);
        $this->start_time   = mktime();
    }
    public function __destruct() {
        if ($this->isRunning()) $this->close();
    }
    public function close() {
        pclose($this->resource);
    }
    public function isRunning() {
        $status = proc_get_status($this->resource);
        return $status["running"];
    }
    public function read($offset=0) {
        $temp = stream_get_contents($this->pipes[1],-1,$offset);
        return $temp;
    }
}
?>