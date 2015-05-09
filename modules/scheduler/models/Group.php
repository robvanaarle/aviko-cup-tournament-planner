<?php

namespace modules\scheduler\models;

class Group extends \ultimo\orm\Model {
  public $id;
  public $name;
  public $tournament_id;
  public $index;
  public $enabled = true;
  
  static protected $fields = array('id', 'name', 'tournament_id', 'index', 'enabled');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'tournament' => array('Tournament', array('tournament_id' => 'id'), self::MANY_TO_ONE),
    'standings' => array('Standings', array('id' => 'group_id'), self::ONE_TO_MANY),
    'matches' => array('Match', array('id' => 'group_id'), self::ONE_TO_MANY),
    'group_teams' => array('GroupTeam', array('id' => 'group_id'), self::ONE_TO_MANY)
  );
  
  static protected $plugins = array('Sequence');
  static public $_sequenceGroupFields = array('tournament_id');
  
  static protected $scopes = array('forTournament', 'withTournament', 'withTeams');
  
  static public function forTournament($tournament_id) {
    return function ($q) use ($tournament_id) {
      $q->where('tournament_id = ?', array($tournament_id));
    };
  }
  
  static public function withTournament() {
    return function ($q) {
      $q->with('@tournament');
    };
  }
  
  static public function withTeams() {
    return function ($q) {
      $q->with('@group_teams')
        ->with('@group_teams.team')
        ->order('@group_teams.index', 'ASC');
    };
  }
}