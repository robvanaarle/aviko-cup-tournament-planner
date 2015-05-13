<?php

namespace modules\scheduler\controllers;

class TeamController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
  }
  
  public function actionRead() {
    $id = $this->request->getParam('id');
    $team = $this->manager->Team->get($id);

    if ($team === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Team with id '{$id}' does not exist.", 404);
    }
    
    $this->view->team = $team;
    
    
    $this->view->matches = $team->matches()->withTeamsAndField()->withGroupAndTournament()->all();
    
  }
  
  public function actionUpdate() {
    $id = $this->request->getParam('id');
    $team = $this->manager->Team->get($id);

    if ($team === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Team with id '{$id}' does not exist.", 404);
    }
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'team\UpdateForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $team->name = $form['name'];
        $team->save();
        
        return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'id' => $team->id));
      }
    } else {
      $form->fromArray($team->toArray());
    }
    
    $this->view->form = $form;
    $this->view->team = $team;
  }
}