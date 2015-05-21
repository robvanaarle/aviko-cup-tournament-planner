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
    $this->view->fields = $tournament->relatedFields()->all();
    $this->view->tournament = $tournament;
  }
  
  public function actionCreate() {
    $form = $this->module->getPlugin('formBroker')->createForm(
      'tournament\CreateForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $tournament = $this->manager->Tournament->create();
        $tournament->name = $form['name'];
        $tournament->starts_at = $form['starts_at'];
        $tournament->match_duration = $form['match_duration'];
        $tournament->between_duration = $form['between_duration'];
        $tournament->show_in_dashboard = ($form['show_in_dashboard'] == true);
        $tournament->save();
        
        return $this->getPlugin('redirector')->redirect(array('action' => 'index'));
      }
    } else {
      $form['starts_at'] = date("Y-m-d H:i:s");
    }
    
    $this->view->form = $form;
  }
  
  public function actionUpdate() {
    $id = $this->request->getParam('id');
    $tournament = $this->manager->Tournament->get($id);
    
    if ($tournament === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Tournament with id '{$id}' does not exist.", 404);
    }
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'tournament\UpdateForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $tournament->name = $form['name'];
        $tournament->starts_at = $form['starts_at'];
        $tournament->match_duration = $form['match_duration'];
        $tournament->between_duration = $form['between_duration'];
        $tournament->show_in_dashboard = ($form['show_in_dashboard'] == true);
        $tournament->save();
        
        return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'id' => $tournament->id));
      }
    } else {
      $form->fromArray($tournament->toArray());
    }
    
    $this->view->form = $form;
    
    $this->view->tournament = $tournament;
  }
  
  public function actionDelete() {
    $id = $this->request->getParam('id');
    $tournament = $this->manager->Tournament->get($id);
    
    if ($tournament === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Tournament with id '{$id}' does not exist.", 404);
    }
    
    $tournament->delete();
    
    return $this->getPlugin('redirector')->redirect(array('action' => 'index'));
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
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      'tournament\NextPhaseForm',
      $this->request->getParam('form', array())
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $newTournament = $tournament->createNextPhaseTournament($form['name'], $form['starts_at']);
        
        return $this->getPlugin('redirector')->redirect(array('action' => 'read', 'id' => $newTournament->id));
      }
    } else {
      $form->fromArray(array(
          'name' => $tournament->name . ' - volgende fase',
          'starts_at' => \DateTime::createFromFormat('Y-m-d H:i:s', $tournament->starts_at)->add(new \DateInterval('P1D'))->format('Y-m-d H:i:s')
      ));
    }
    
    $this->view->form = $form;
    
    $ticketGroups = $tournament->getNextPhaseTicketGroups();
    $this->view->ticketGroups = $ticketGroups;
    $this->view->decisions = \ats\DecisionSet::fromTicketGroups($ticketGroups);
    
    $this->view->groups = $tournament->related('groups')->orderByIndex()->withFullStandings()->all();
    
    $this->view->tournament = $tournament;
  }
}