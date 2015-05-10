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
  }
  
}