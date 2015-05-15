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
  
  static protected $scopes = array('forGroup', 'withTeamsAndField', 'forTournament', 
      'withGroup', 'forTeam', 'withGroupAndTournament', 'played', 'future', 'current',
      'orderByStart');
  
  static protected $fetchers = array('groupedByStart');
  
  static public function groupedByStart($s) {
    $result = array();
    $prevMatch = null;
    $group = array();
    

    $s->scope(function ($q) { $q->order('@starts_at', 'ASC'); });
    
    foreach ($s->all(true) as $match) {
      if ($prevMatch !== null && $match->starts_at != $prevMatch->starts_at) {
        $result[] = $group;
        $group = array();
      }
      $group[] = $match;
    }
    
    if (!empty($group)) {
      $result[] = $group;
    }
    
    return $result;
  }

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
        ->order('starts_at', 'ASC');
    };
  }
  
  static public function forTeam($team_id) {
    return function ($q) use ($team_id) {
      $q->where('@home_team_id = ? OR @away_team_id = ?', array($team_id, $team_id))
        ->order('starts_at', 'ASC');
    };
  }
  
  static public function orderByStart() {
    return function ($q) {
      $q->order('starts_at', 'ASC');
    };
  }
  
  static public function notPlayed() {
    return function ($q) {
      $q->where('@goals_home IS NULL OR @goals_awa IS NULL');
    };
  }
  
  static public function played() {
    return function ($q) {
      $q->where('@goals_home IS NOT NULL AND @goals_away IS NOT NULL');
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
        ->with('@field')
        ->order('@field.index', 'ASC');
    };
  }
  
  static public function future() {
    return function ($q) {
      $q->where('@starts_at >= ?', array(date("Y-m-d 00:00:00")))
        ->order('@starts_at', 'ASC');
        
    };
  }
  
  static public function current() {
    return function ($q) {
      $q->where('@starts_at >= ? AND @starts_at <= ?', array(date("Y-m-d H:i:s", time()-30*60), date("Y-m-d H:i:s", time()+30*60)))
        ->order('@starts_at', 'ASC');
    };
  }
  
  public function fields() {
    $group = $this->related('group')->first();
    $tournament = $group->related('tournament')->first();
    return $tournament->fields();
  }

  public function homeWins() {
    return $this->goals_home > $this->goals_away;
  }
    
  public function awayWins() { 
    return $this->goals_away > $this->goals_home;
  }
    
  public function tie() {
    return $this->goals_away == $this->goals_home;
  }
  
  public function involvesTeamId($teamId) {
    return $this->home_team_id == $teamId || $this->away_team_id == $teamId;
  }
  
}