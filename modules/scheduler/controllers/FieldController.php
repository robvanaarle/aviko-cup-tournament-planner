<?php

namespace modules\scheduler\controllers;

class FieldController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
  }
  
  public function actionIndex() {
    $this->view->fields = $this->manager->Field->orderByIndex()->all();
  }
  
  public function actionMove() {
    $id = $this->request->getParam('id');
    $field = $this->manager->Field->get($id);
    
    if ($field === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Field with id '{$id}' does not exist.", 404);
    }

    $field->move($this->request->getParam('count', 0));
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'index'));
  }
}