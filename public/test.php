<?php
$sa = 'vH0nfLQ/6Z0LjrB++aWWoEeiDo1pUtQHneOWbBAKoc898BsdzNdAycUkA3gZumLva4MX5OykIDZtU0tMiBNQ2Q==';
$sb = '18MktpDofazP2lfcOO9YZetyg2Axoxz5aHa03dXzrNKnYEHZsWaOBaKreWT3/GLScK7sAywour+CpccaLGBDBA==';

$password = "blaat";

echo "$sa\n$sb\n";

$runs = 100000;

$start = microtime(true);

while($runs--) {
  $password = hash('sha256', $sa . $sb . $password);
}
echo $password . "\n";
$end = microtime(true);

echo ($end-$start);

