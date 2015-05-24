<?php

namespace modules\scheduler\controllers;

class TournamentController extends \ucms\tournamentplanner\controllers\TournamentController {
  
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