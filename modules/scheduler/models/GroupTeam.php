<?php

namespace modules\scheduler\models;

class GroupTeam extends \ultimo\orm\Model {
  public $id;
  public $group_id;
  public $team_id;
  public $index;
  
  const FIELD_TYPE_WHOLE = 'whole';
  const FIELD_TYPE_HALF = 'half';
  
  static protected $fields = array('id', 'group_id', 'team_id', 'index');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'group' => array('Group', array('group_id' => 'id'), self::MANY_TO_ONE),
    'team' => array('Team', array('team_id' => 'id'), self::MANY_TO_ONE)
  );
  
  static protected $plugins = array('Sequence');
  static public $_sequenceGroupFields = array('group_id');
}