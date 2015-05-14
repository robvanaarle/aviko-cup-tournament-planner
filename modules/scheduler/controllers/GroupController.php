<?php

namespace modules\scheduler\controllers;

class GroupController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
  }
  
  public function actionRead() {
    $id = $this->request->getParam('id');
    $group = $this->manager->Group->get($id);
    
    if ($group === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Group with id '{$id}' does not exist.", 404);
    }
    
    $this->view->tournament = $group->related('tournament')->first();
    $this->view->teams = $group->teams()->all();
    $this->view->matches = $group->related('matches')->withTeamsAndField()->all();
    $this->view->group = $group;
    $this->view->standings = $group->related('standings')->orderByIndex()->withTeam()->all();
  }
  
  public function actionUpdate() {
    $id = $this->request->getParam('id');
    $group = $this->manager->Group->get($id);

    if ($group === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Group with id '{$id}' does not exist.", 404);
    }
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'group\UpdateForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $group->name = $form['name'];
        $group->save();
        
        return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'id' => $group->id));
      }
    } else {
      $form->fromArray($group->toArray());
    }
    
    $this->view->form = $form;
    $this->view->group = $group;
    $this->view->tournament = $group->related('tournament')->first();
  }
  
  public function actionMove() {
    $id = $this->request->getParam('id');
    $group = $this->manager->Group->get($id);
    
    if ($group === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Group with id '{$id}' does not exist.", 404);
    }

    $group->move($this->request->getParam('count', 0));
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'controller' => 'tournament', 'id' => $group->tournament_id));
  }
  
  public function actionSyncstandings() {
    $id = $this->request->getParam('id');
    $group = $this->manager->Group->get($id);
    
    if ($group === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Group with id '{$id}' does not exist.", 404);
    }
    
    $group->syncStandings();
    return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'controller' => 'group', 'id' => $group->id));
  }
  
}