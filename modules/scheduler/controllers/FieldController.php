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
    $this->view->fields = $this->manager->Field->withoutUnknown()->orderByName()->all();
  }
  
    public function actionRead() {
    $id = $this->request->getParam('id');
    $field = $this->manager->Field->get($id);

    if ($field === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Field with id '{$id}' does not exist.", 404);
    }
    
    $this->view->field = $field;
    
    $this->view->matches = $field->related('matches')->orderByStart()->withTeamsAndField()->withGroupAndTournament()->all();
  }
  
  public function actionCreate() {

    $form = $this->module->getPlugin('formBroker')->createForm(
      'field\CreateForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $field = $this->manager->Field->create();
        $field->name = $form['name'];
        $field->save();
        
        return $this->getPlugin('redirector')->redirect(array('action' => 'index'));
      }
    }
    
    $this->view->form = $form;
  }
  
  public function actionUpdate() {
    $id = $this->request->getParam('id');
    $field = $this->manager->Field->get($id);

    if ($field === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Field with id '{$id}' does not exist.", 404);
    }
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'field\UpdateForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $field->name = $form['name'];
        $field->save();
        
        return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'id' => $field->id));
      }
    } else {
      $form->fromArray($field->toArray());
    }
    
    $this->view->form = $form;
    $this->view->field = $field;
  }
  
}