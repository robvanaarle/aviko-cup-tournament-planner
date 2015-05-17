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
  
  public function beforeDelete() {
    // delete team matches and standing for the group
    $standing = $this->_manager->select('Standing')
                               ->where('@group_id = ?', array($this->group_id))
                               ->where('@team_id = ?', array($this->team_id))
                               ->first();
    if ($standing !== null) {
      $standing->delete();
    }
    
    $this->_manager->select('Match')
                   ->where('@group_id = ?', array($this->group_id))
                   ->where('(@home_team_id = ? OR @away_team_id = ?)', array($this->team_id, $this->team_id))
                   ->delete();
  }
}