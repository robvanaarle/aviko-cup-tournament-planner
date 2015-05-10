<?php

namespace modules\scheduler\models;

class Team extends \ultimo\orm\Model {
  public $id;
  public $name;
  
  static protected $fields = array('id', 'name');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'standing' => array('Standing', array('id' => 'team_id'), self::ONE_TO_MANY),
    'group_teams' => array('GroupTeam', array('id' => 'team_id'), self::ONE_TO_MANY)
  );
  
  static protected $scopes = array('forGroup');
   
  static public function forGroup($group_id) {
    return function ($q) use ($group_id) {
      $q->with('@group_teams')
        ->where('@group_teams.group_id = ?', array($group_id))
        ->order('@group_teams.index', 'ASC');
    };
  }
  
  public function matches() {
    $model = $this;
    $staticModel = $this->_manager->getStaticModel('Match');
    $staticModel->scope(function ($q) use ($model) {
      $q->where('@home_team_id = ? OR @away_team_id = ?', array($model->id, $model->id))
        ->order('starts_at', 'ASC')
        ->order('id', 'ASC');
    });
    return $staticModel;
  }
}