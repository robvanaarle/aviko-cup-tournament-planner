<?php

namespace modules\general\controllers;

class ErrorController extends \ultimo\mvc\Controller {
  public function actionError() {
    $exceptions = $this->request->getParam('exceptions');
    
    $httpStatusCode = 404;
    foreach ($exceptions as $exception) {
      if (!$exception instanceof \ultimo\mvc\exceptions\DispatchException || $exception->getCode() != \ultimo\mvc\exceptions\DispatchException::PAGE_NOT_FOUND) {
        $httpStatusCode = 500;
        break;
      }
    }
    
    $this->application->getResponse()->setStatusCode($httpStatusCode);
    
    $this->view->httpStatusCode = $httpStatusCode;
    $this->view->exceptions = $exceptions;
    $this->view->request = $this->request->getParam('request');
  }
  
  public function actionException() {
    throw new \InvalidArgumentException('This is a test exception', 666, new \Exception('This is a previous exception'));
  }
  
  public function actionWarning() {
    trigger_error('This is a test notice', E_USER_WARNING);
  }
  
  public function actionError2() {
    trigger_error('THis is a test error', E_USER_ERROR);
  }
  
  public function actionFatal() {
    unexisting_function('Testing');
  }
}