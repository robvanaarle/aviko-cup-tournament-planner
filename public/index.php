<?php
$startTime = microtime(true);
date_default_timezone_set('Europe/Amsterdam');
error_reporting(-1);

// for scssphp (also need to remove this from phptpl package
set_include_path(get_include_path() . PATH_SEPARATOR . '../');

if (strpos($_SERVER['HTTP_HOST'], 'aviko.lan') !== false) {
  $environment = 'development';
  //$basePath = '';
  ini_set('display_errors', 1);
} else {
  $environment = 'production';
  //$basePath = '';
  //if (isset($_GET['__basePath'])) {
  //  $basePath = $_GET['__basePath'];
 // }
  ini_set('display_errors', 0);
}

require '../vendor/autoload.php';

session_name('aviko_tournament_schedule');

$errorCarerConfigs = array(
    'production' => array(
        'email_to' => 'rvanaarle@gmail.com',
        'email_from' => 'KEK ErrorCarer <errorcarer@elfkroegetocht.nl>',
        'email_subject' => '[AvikoTS] %s: \'%s\'',
        'print_errors' => false,
        'response' => __DIR__ . '/ats/error.html'
    ),
    'development' => array(
        'print_errors' => true
    )
);

// register ErrorCarer as early as possible
//require_once('ultimo/debug/error/ErrorCarer.php');
$errorHandler = new \ultimo\debug\error\ErrorCarer($errorCarerConfigs[$environment]);
$errorHandler->register();

$app = new \ultimo\mvc\Application('aviko', '../');

$request = $app->getSapi()->getRequest(new \ultimo\mvc\Request());
//$request->setBasePath($basePath);

$app->setRegistry('ultimo.debug.error.ErrorHandler', $errorHandler)
    ->setEnvironment($environment)
    ->runBootstrap()
    ->run($request);

$errorHandler->unregister();
$endTime = microtime(true);
$totalTime = $endTime - $startTime;
//
//if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
//  echo '<br /><br />End of script, ' . $totalTime . ' secs';
//}

