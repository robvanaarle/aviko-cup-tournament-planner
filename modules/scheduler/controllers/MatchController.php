<?php

namespace modules\scheduler\controllers;

class MatchController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
  }
  
  public function actionUpdate() {
    $id = $this->request->getParam('id');
    $match = $this->manager->Match->withTeamsAndField()->withGroup()->byId($id)->first();

    if ($match === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Match with id '{$id}' does not exist.", 404);
    }
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'match\UpdateForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $match->goals_home = empty($form['goals_home']) ? null : $form['goals_home'];
        $match->goals_away = empty($form['goals_away']) ? null : $form['goals_away'];
        $match->save();
        
        $group = $this->manager->Group->get($match->group_id);
        $group->syncStandings();
        
        $tournament = $this->manager->Tournament->byGroup($match->group_id)->first();
        
        return $this->getPlugin('redirector')->redirect(array('controller' => 'tournament', 'action' => 'read', 'id' => $tournament->id));
      }
    } else {
      $form->fromArray($match->toArray());
    }
    
    $this->view->form = $form;
    $this->view->match = $match;
  }
}