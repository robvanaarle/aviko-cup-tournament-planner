<?php

namespace modules\scheduler\models;

class Field extends \ultimo\orm\Model {
  public $id;
  public $name;
  public $field_type;
  
  const FIELD_TYPE_WHOLE = 'whole';
  const FIELD_TYPE_HALF = 'half';
  
  static protected $fields = array('id', 'name', 'field_type');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'matches' => array('Match', array('id' => 'field_id', self::ONE_TO_MANY)),
    'tournament_fields' => array('TournamentField', array('id' => 'field_id'), self::ONE_TO_MANY)
  );
  
  static protected $scopes = array('withFieldType', 'orderByName');
  
  static public function withFieldType($field_type) {
    return function ($q) use ($field_type) {
      $q->where('@field_type = ?', array($field_type))
        ->order('@index', 'ASC');
    };
  }
  
  static protected $fetchers = array('fetchIdNameHash');
  
  static public function fetchIdNameHash($s) {
    $result = array();
    foreach ($s->all() as $field) {
      $result[$field->id] = $field->name;
    }
    return $result;
  }
  
  static public function orderByName() {
    return function ($q) {
      $q->order('@name', 'ASC');
    };
  }
  
  static protected $plugins = array('Sequence');
}