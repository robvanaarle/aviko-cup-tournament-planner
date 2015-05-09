<?php

namespace modules\scheduler\models;

class Tournament extends \ultimo\orm\Model {
  public $id;
  public $name;
  public $field_type;
  public $match_duration;
  public $between_duration;
  public $starts_at;
  public $index;
  
  public $groups = array();
  
  const FIELD_TYPE_WHOLE = 'whole';
  const FIELD_TYPE_HALF = 'half';
  
  static protected $fields = array('id', 'name', 'field_type', 'match_duration', 'between_duration', 'starts_at', 'index');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'groups' => array('Group', array('id' => 'tournament_id'), self::ONE_TO_MANY)
  );
  
  static protected $plugins = array('Sequence');
  
  static protected $scopes = array('byGroup');
  
  static public function byGroup($group_id) {
    return function ($q) use ($group_id) {
      $q->with('@groups')
        ->where('@groups.id = ?', array($group_id));
    };
  }
  
  public function regenerateSchedule() {
    $groups = $this->_manager->Group->forTournament($this->id)->all();
    
    $groupSchedules = array();
    
    $maxRounds = 0;
    $maxRoundMatches = array();

    foreach ($groups as $group) {
      $this->_manager->Match->query()->where('@group_id = ?', array($group->id))->delete();
      
      $teams = $this->_manager->Team->forGroup($group->id)->all();
      $scheduler = new \ats\SingleRoundRobin($teams);
      $schedule = $scheduler->generateSchedule();
      $groupSchedules[$group->id] = $schedule;
      
      $maxRounds = max($maxRounds, count($schedule));
      foreach ($schedule as $index => $round) {
        if (!isset($maxRoundMatches[$index])) {
          $maxRoundMatches[$index] = 0;
        }
        
        $maxRoundMatches[$index] = max($maxRoundMatches[$index], count($round));
      }
    }
    
    $startsAt = \DateTime::createFromFormat('Y-m-d H:i:s', $this->starts_at);
    $duration = new \DateInterval("PT" . ($this->match_duration + $this->between_duration) . "M");
    $fields = $this->_manager->Field->forFieldType($this->field_type)->all();
    
    
    $fieldIndex = 0;
    $teamsThisTimeslot = array();
    
    for ($roundIndex = 0; $roundIndex < $maxRounds; $roundIndex++) {
      
      
      foreach ($groupSchedules as $groupId => $groupSchedule) {
        if (!isset($groupSchedule[$roundIndex])) {
          continue;
        }
        $round = $groupSchedule[$roundIndex];
        
        for ($matchIndex = 0; $matchIndex < $maxRoundMatches[$roundIndex]; $matchIndex++) {
          if (!isset($round[$matchIndex])) {
            break;
          }
          $match = $round[$matchIndex];
          
          // prevent two matches of the same team in one timeslot
          if (in_array($match['home']->id, $teamsThisTimeslot) || in_array($match['away']->id, $teamsThisTimeslot)) {
            $fieldIndex = 0;
            $teamsThisTimeslot = array();
            $startsAt->add($duration);
          }
          
          $matchObject = $this->_manager->Match->create();
          $matchObject->home_team_id = $match['home']->id;
          $matchObject->away_team_id = $match['away']->id;
          $matchObject->field_id = $fields[$fieldIndex]->id;
          $matchObject->starts_at = $startsAt->format("Y-m-d H:i:s");
          $matchObject->group_id = $groupId;
          $matchObject->referee = '';
          $matchObject->save();
          
          //echo "match {$matchIndex} of round {$roundIndex} for group {$groupId} at field {$fieldIndex}<br />";
          
          $teamsThisTimeslot[] = $match['home']->id;
          $teamsThisTimeslot[] = $match['away']->id;
          $fieldIndex++;
          if ($fieldIndex >= count($fields)) {
            $fieldIndex = 0;
            $teamsThisTimeslot = array();
            $startsAt->add($duration);
          }
        }
      }
    }
  }
  
  
}