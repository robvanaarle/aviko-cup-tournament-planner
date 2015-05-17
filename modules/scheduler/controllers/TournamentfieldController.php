<?php

namespace modules\scheduler\controllers;

class TournamentfieldController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
  }
  
  public function actionCreate() {
    $tournamentId = $this->request->getParam('tournament_id');
    $tournament = $this->manager->Tournament->get($tournamentId);

    if ($tournament === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Tournament with id '{$tournamentId}' does not exist.", 404);
    }
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'tournamentfield\CreateForm',
      $this->request->getParam('form', array()),
      array(
        'availableFields' => $tournament->availableFields()->orderByName()->fetchIdNameHash()
      )
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $tournamentField = $this->manager->TournamentField->create();
        $tournamentField->field_id = $form['field_id'];
        $tournamentField->tournament_id = $tournament->id;
        $tournamentField->save();
        
        return $this->getPlugin('redirector')->redirect(array('controller' => 'tournament', 'action' => 'read', 'id' => $tournament->id));
      }
    }
    
    $this->view->form = $form;
    $this->view->tournament = $tournament;
  }
  
  public function actionMove() {
    $id = $this->request->getParam('id');
    $tournamentField = $this->manager->TournamentField->get($id);
    
    if ($tournamentField === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("TournamentField with id '{$id}' does not exist.", 404);
    }
    
    $tournamentField->move($this->request->getParam('count', 0));
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'controller' => 'tournament', 'id' => $tournamentField->tournament_id));
  }
  
  public function actionDelete() {
    $id = $this->request->getParam('id');
    $tournamentField = $this->manager->TournamentField->get($id);
    
    if ($tournamentField === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("TournamentField with id '{$id}' does not exist.", 404);
    }
    
    $tournamentField->delete();
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'controller' => 'tournament', 'id' => $tournamentField->tournament_id));
  }
  
}