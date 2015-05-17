<?php

namespace modules\scheduler\models;

class TournamentField extends \ultimo\orm\Model {
  public $id;
  public $tournament_id;
  public $field_id;
  public $index;
  
  static protected $fields = array('id', 'tournament_id', 'field_id', 'index');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'field' => array('Field', array('field_id' => 'id'), self::MANY_TO_ONE),
    'tournament' => array('Tournament', array('tournament_id' => 'id'), self::MANY_TO_ONE)
  );
  
  static protected $plugins = array('Sequence');
  static public $_sequenceGroupFields = array('tournament_id');
  
  public function beforeDelete() {
    // set the field_id to null of all matches on this field in this tournament
    $this->_manager->select('Match')
                   ->where('@field_id = ?', array($this->field_id))
                   ->with('@group')
                   ->where('@group.tournament_id = ?', array($this->tournament_id))
                   ->set('@field_id = ?', array(0))
                   ->update();          
  }
}