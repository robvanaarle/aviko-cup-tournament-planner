<?php

namespace modules\general\controllers;

class AuthController extends \ultimo\mvc\Controller {
  public function actionAccessdenied() {
    $this->view->deniedRequest = $this->request->getParam('deniedRequest');
  }
}