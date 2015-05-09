<?php

namespace modules\scheduler\models;

class Standings extends \ultimo\orm\Model {
  public $id;
  public $group_id;
  public $team_id;
  public $won;
  public $drawn;
  public $lost;
  public $goals_for;
  public $goals_against;
  public $position;
  
  static protected $fields = array('id', 'group_id', 'team_id', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'position');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'group' => array('Group', array('group_id' => 'id'), self::MANY_TO_ONE)
  );
}