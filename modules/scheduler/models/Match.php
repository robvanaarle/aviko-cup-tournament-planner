<?php

namespace modules\scheduler\models;

class Match extends \ultimo\orm\Model {
  public $id;
  public $home_team_id;
  public $away_team_id;
  public $goals_home;
  public $goals_away;
  public $field_id;
  public $starts_at;
  public $referee = '';
  public $group_id;
  
  static protected $fields = array('id', 'home_team_id', 'away_team_id', 'goals_home', 'goals_away', 'field_id', 'starts_at', 'referee', 'group_id');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'home_team' => array('Team', array('home_team_id' => 'id'), self::MANY_TO_ONE),
    'away_team' => array('Team', array('away_team_id' => 'id'), self::MANY_TO_ONE),
    'group' => array('Group', array('group_id' => 'id'), self::MANY_TO_ONE),
    'field' => array('Field', array('field_id' => 'id'), self::MANY_TO_ONE)
  );
  
  static protected $scopes = array('forGroup', 'withTeamsAndField', 'forTournament', 'withGroup', 'forTeam', 'withGroupAndTournament');
  
  static public function forGroup($group_id) {
    return function ($q) use ($group_id) {
      $q->where('group_id = ?', array($group_id))
        ->order('starts_at', 'ASC')
        ->order('id', 'ASC');
    };
  }
  
  static public function forTournament($tournament_id) {
    return function ($q) use ($tournament_id) {
      $q->with('@group')
        ->where('@group.tournament_id = ?', array($tournament_id))
        ->order('starts_at', 'ASC')
        ->order('id', 'ASC');
    };
  }
  
  static public function forTeam($team_id) {
    return function ($q) use ($team_id) {
      $q->where('@home_team_id = ? OR @away_team_id = ?', array($team_id, $team_id))
        ->order('starts_at', 'ASC')
        ->order('id', 'ASC');
    };
  }
  
  static public function withGroup() {
    return function ($q) {
      $q->with('@group');
    };
  }
  
  static public function withGroupAndTournament() {
    return function ($q) {
      $q->with('@group')
        ->with('@group.tournament');
    };
  }
  
  static public function withTeamsAndField() {
    return function ($q) {
      $q->with('@home_team')
        ->with('@away_team')
        ->with('@field');
    };
  }
}