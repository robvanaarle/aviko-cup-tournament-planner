<?php

namespace modules\scheduler\controllers;

class TournamentController extends \ultimo\mvc\Controller {
  
  protected $manager;
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
    $this->manager = $this->module->getPlugin('uorm')->getManager();
  }
  
  public function actionIndex() {
    $this->view->tournaments = $this->manager->Tournament->orderByIndex()->all();
  }
  
  public function actionRead() {
    $id = $this->request->getParam('id');
    $tournament = $this->manager->Tournament->get($id);
    
    if ($tournament === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Tournament with id '{$id}' does not exist.", 404);
    }

    $this->view->groups = $tournament->related('groups')->orderByIndex()->all();
    $this->view->matches = $tournament->matches()->withTeamsAndField()->all();
    $this->view->tournament = $tournament;
  }
  
  public function actionMove() {
    $id = $this->request->getParam('id');
    $tournament = $this->manager->Tournament->get($id);
    
    if ($tournament === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Tournament with id '{$id}' does not exist.", 404);
    }

    $tournament->move($this->request->getParam('count', 0));
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'index', 'controller' => 'tournament'));
  }
  
  public function actionGenerateschedule() {
    $id = $this->request->getParam('id');
    $tournament = $this->manager->Tournament->get($id);
    
    if ($tournament === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Tournament with id '{$id}' does not exist.", 404);
    }
    
    $tournament->regenerateSchedule();
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'controller' => 'tournament', 'id' => $tournament->id));
  }
  
  public function actionNextphase() {
    $id = $this->request->getParam('id');
    $tournament = $this->manager->Tournament->get($id);
    
    if ($tournament === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Tournament with id '{$id}' does not exist.", 404);
    }
    
    //if ($this->request->getParam('call')) {
      $this->view->newGroups = $tournament->nextPhase2();
      $this->view->decisions = \ats\DecisionSet::fromTicketGroups($this->view->newGroups);
    //}
    
    $this->view->groups = $tournament->related('groups')->orderByIndex()->withFullStandings()->all();
    
    $this->view->tournament = $tournament;
  }
}