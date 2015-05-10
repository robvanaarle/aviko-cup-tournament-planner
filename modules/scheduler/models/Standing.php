<?php

namespace modules\scheduler\models;

class Standing extends \ultimo\orm\Model {
  public $id;
  public $group_id;
  public $team_id;
  public $won = 0;
  public $drawn = 0;
  public $lost = 0;
  public $goals_for = 0;
  public $goals_against = 0;
  
  static protected $fields = array('id', 'group_id', 'team_id', 'won', 'drawn', 'lost', 'goals_for', 'goals_against');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'group' => array('Group', array('group_id' => 'id'), self::MANY_TO_ONE),
    'team' => array('Team', array('team_id' => 'id'), self::MANY_TO_ONE)
  );
  static protected $plugins = array('Sequence');
  static protected $fetchers = array('getWithTeamsAsKey');
  
  static public $_sequenceGroupFields = array('group_id');
  static public $scopes = array('withTeam');
  
  static public function getWithTeamsAsKey($s) {
    $result = array();
    foreach ($s->all() as $standing) {
      $result[$standing->team_id] = $standing;
    }
    return $result;
  }
  
  static public function withTeam() {
    return function ($q) {
      $q->with('@team');
    };
  }
  
  public function reset() {
    $this->won = 0;
    $this->drawn = 0;
    $this->lost = 0;
    $this->goals_for = 0;
    $this->goals_against = 0;
  }
  
  public function getPlayed() {
    return $this->won + $this->drawn + $this->lost;
  }
    
  public function getPoints() {
    return ($this->won * 3) + $this->drawn;
  }
    
  public function getGoalsDifference() {
    return $this->goals_for - $this->goals_against;
  }
  
  public function getPosition() {
    return $this->index + 1;
  }
  
  public function compareTo(Standing $standing) {
    if ($this->getPoints() > $standing->getPoints()) {
      return 1;
    } elseif ($this->getPoints() < $standing->getPoints()) {
      return -1;
    }

    if ($this->getGoalsDifference() > $standing->getGoalsDifference()) {
    return 1;
    } elseif ($this->getGoalsDifference() < $standing->getGoalsDifference()) {
        return -1;
    }

    if ($this->goals_for > $standing->goals_for) {
      return 1;
    } elseif ($this->goals_for < $standing->goals_for) {
      return -1;
    }

    return 0;
  }
}