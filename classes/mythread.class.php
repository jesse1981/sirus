<?php
/*
 * http://php.net/manual/en/pthreads.installation.php
 * http://stackoverflow.com/questions/70855/how-can-one-use-multi-threading-in-php-applications
*/
class mythread extends Thread {
    public function __construct($arg) {
        $this->arg = $arg;
    }

    public function run() {
        if ($this->arg) {
            $sleep = mt_rand(1, 10);
            printf('%s: %s  -start -sleeps %d' . "\n", date("g:i:sa"), $this->arg, $sleep);
            sleep($sleep);
            printf('%s: %s  -finish' . "\n", date("g:i:sa"), $this->arg);
        }
    }
}
?>