<?php

namespace modules\scheduler\controllers;

class GroupTeamController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
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
  
}