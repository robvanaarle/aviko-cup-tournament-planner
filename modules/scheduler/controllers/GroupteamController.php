<?php

namespace modules\scheduler\controllers;

class GroupteamController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
  }
  
  public function actionCreate() {
    $groupId = $this->request->getParam('group_id');
    $group = $this->manager->Group->get($groupId);

    if ($group === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Group with id '{$groupId}' does not exist.", 404);
    }
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'groupteam\CreateForm',
      $this->request->getParam('form', array()),
      array(
        'availableTeams' => $group->related('tournament')->first()->availableTeams()->orderByName()->fetchIdNameHash()
      )
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $groupteam = $this->manager->GroupTeam->create();
        $groupteam->team_id = $form['team_id'];
        $groupteam->group_id = $group->id;
        $groupteam->save();
        
        return $this->getPlugin('redirector')->redirect(array('controller' => 'group', 'action' => 'read', 'id' => $group->id));
      }
    }
    
    $this->view->form = $form;
    $this->view->group = $group;
    $this->view->tournament = $group->related('tournament')->first();
  }
  
  public function actionMove() {
    $id = $this->request->getParam('id');
    $groupTeam = $this->manager->GroupTeam->get($id);
    
    if ($groupTeam === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("GroupTeam with id '{$id}' does not exist.", 404);
    }
    
    $groupTeam->move($this->request->getParam('count', 0));
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'controller' => 'group', 'id' => $groupTeam->group_id));
  }
  
  public function actionDelete() {
    $id = $this->request->getParam('id');
    $groupTeam = $this->manager->GroupTeam->get($id);
    
    if ($groupTeam === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("GroupTeam with id '{$id}' does not exist.", 404);
    }
    
    $groupTeam->delete();
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'controller' => 'group', 'id' => $groupTeam->group_id));
  }
  
}