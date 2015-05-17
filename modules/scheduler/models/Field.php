<?php

namespace modules\scheduler\models;

class Field extends \ultimo\orm\Model {
  public $id;
  public $name;
  
  static protected $fields = array('id', 'name');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'matches' => array('Match', array('id' => 'field_id'), self::ONE_TO_MANY),
    'tournament_fields' => array('TournamentField', array('id' => 'field_id'), self::ONE_TO_MANY)
  );
  
  static protected $scopes = array('withFieldType', 'orderByName', 'withoutUnknown');
  
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
  
  static public function withoutUnknown() {
    return function ($q) {
      $q->where('@id <> ?', array(0));
    };
  }
}