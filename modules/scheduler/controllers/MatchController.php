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
      $this->request->getParam('form', array()),
      array(
        'availableFields' => $match->fields()->fetchIdNameHash()
      )
    );
     
    if ($this->request->isPost()) {
      if ($form->validate()) {
        $match->goals_home = ($form['goals_home'] == '') ? null : $form['goals_home'];
        $match->goals_away = ($form['goals_away'] == '') ? null : $form['goals_away'];
        $match->field_id = $form['field_id'];
        $match->starts_at = $form['starts_at'];
        $match->save();
        
        $group = $this->manager->Group->get($match->group_id);
        $group->syncStandings();
        
        $tournament = $this->manager->Tournament->byGroup($match->group_id)->first();
        
        if (isset($form['returnUrl'])) {
          return $this->getPlugin('redirector')->setRedirectUrl($form['returnUrl']);
        } else {
          return $this->getPlugin('redirector')->redirect(array('controller' => 'tournament', 'action' => 'read', 'id' => $tournament->id));
        }
      }
    } else {
      $form->fromArray($match->toArray());
    }
    
    $this->view->form = $form;
    $this->view->match = $match;
  }
  
  public function actionForms() {
    $this->view->matches = $this->manager->Match->future()->withTeamsAndField()->withGroupAndTournament()->all();
  }
  
  public function actionCurrent() {
    $this->view->matches = $this->manager->Match->current()->withTeamsAndField()->withGroupAndTournament()->all();
  }
  
  public function actionDashboard() {
    $this->view->tournaments = $this->manager->Tournament->forDashboard()->all();
  }
  
  public function actionExport() {
    $tournaments = $this->manager->Tournament->forExport()->all();
    
    $delim = ';';
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment;filename=wedstrijden_'.date('Ymd_His').'.csv');
    
    $out = fopen('php://output', 'w');
    
    fputcsv($out, array('start', 'veld', 'toernooi', 'poule', 'thuis', 'uit', 'goals thuis', '', 'goals uit', '#'), $delim);

    foreach ($tournaments as $tournament) {
      $tournamentMatches = $tournament->matches()->withTeamsAndField()->all();
      foreach ($tournamentMatches as $match) {
        fputcsv($out, array(
            $match->starts_at,
            $match->field->name,
            $tournament->name,
            $match->group->name,
            $match->home_team->name,
            $match->away_team->name,
            $match->goals_home,
            '-',
            $match->goals_away,
            $match->id
        ), $delim);
        
        fflush($out);
      }
    }
    
    fclose($out);
    
    $this->getPlugin('viewRenderer')->setDisabled(true);
  }
}