<?php
require_once 'dompdf/class.pdf.php';
$file = directoryToArray('/var/www/classes/dompdf/include', false);
foreach ($file as $f) {
    include $f;
}
?>