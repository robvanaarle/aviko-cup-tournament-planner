<?php
$out = fopen('php://stdout', 'w');
    fputcsv($out, array('a', 'b'));
    fwrite($out, "test'");
    fflush($out);
    fclose($out);