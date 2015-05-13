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
  
  const FIELD_TYPE_WHOLE = 'whole';
  const FIELD_TYPE_HALF = 'half';
  
  static protected $fields = array('id', 'name', 'field_type', 'match_duration', 'between_duration', 'starts_at', 'index', 'relatedFields');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  static protected $relations = array(
    'groups' => array('Group', array('id' => 'tournament_id'), self::ONE_TO_MANY),
    'tournament_fields' => array('TournamentField', array('id' => 'tournament_id'), self::ONE_TO_MANY)
  );
  
  static protected $plugins = array('Sequence');
  
  static protected $scopes = array('byGroup');
  
  static public function byGroup($group_id) {
    return function ($q) use ($group_id) {
      $q->with('@groups')
        ->where('@groups.id = ?', array($group_id));
    };
  }
  
  public function matches() {
    $model = $this;
    $staticModel = $this->_manager->getStaticModel('Match');
    $staticModel->scope(function ($q) use ($model) {
      $q->with('@group')
        ->where('@group.tournament_id = ?', array($model->id))
        ->order('starts_at', 'ASC')
        ->order('id', 'ASC');
    });
    return $staticModel;
  }
  
  public function relatedFields() {
    $model = $this;
    $staticModel = $this->_manager->getStaticModel('Field');
    $staticModel->scope(function ($q) use ($model) {
      $q->with('@tournament_fields')
        ->where('@tournament_fields.tournament_id = ?', array($model->id))
        ->order('@tournament_fields.index', 'ASC');
    });
    return $staticModel;
  }
  
  public function regenerateSchedule() {
    $groups = $this->_manager->Group->forTournament($this->id)->all();
    
    $groupSchedules = array();
    
    $maxRounds = 0;
    $maxRoundMatches = array();

    foreach ($groups as $group) {
      $this->_manager->Match->query()->where('@group_id = ?', array($group->id))->delete();
      
      $teams = $group->teams()->all();
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
    $fields = $this->relatedFields()->all();
    
    
    $fieldIndex = 0;
    $teamsThisTimeslot = array();
    $matchObjects = array();
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
          $matchObjects[] = $matchObject;
          
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
    $this->_manager->Match->multiInsert($matchObjects);
  }
  
  public function nextPhase2() {
    $standingGroups = $this->related('groups')->orderByIndex()->withFullStandings()->getWithGroupsAsKey();
    $ticketGroups = array();
    $indices = 0;
    foreach ($standingGroups as $index => $standingGroup) {
      $ticketGroup = \ats\TicketGroup::fromStandings($standingGroup->standings, $index);
      $ticketGroups[] = $ticketGroup;
      $indices = max($indices, $ticketGroup->size);
    }

    //echo "<u>ticketGroups from original groups</u><br />" . implode("<br />", $ticketGroups) . "<br /><br />";
    

    $indexTickets = array();
    $indexTicketGroups = array();
    for ($i = 0; $i < $indices; $i++) {
      $indexGroup = array();

      foreach ($ticketGroups as $j => $ticketGroup) {
        if ($i < count($ticketGroup->tickets)) {
          $indexGroup[] = $ticketGroup->tickets[$i];
        }
      }

      usort($indexGroup, function($a, $b) {
        return $b->compareStandingTo($a);
      });

      $indexTicketGroup = \ats\TicketGroup::fromIndexTickets($indexGroup);
      $indexTicketGroups[] = $indexTicketGroup;
      $indexTickets = array_merge($indexTickets, $indexTicketGroup->tickets);
    }
    
    //echo "<u>ticketGroups per position</u><br />" . implode("<br />", $indexTicketGroups) . "<br /><br />";

    $targetGroups = array();
    $i = 0;
    foreach ($standingGroups as $index => $standingGroup) {
      $targetGroup = new \ats\TicketGroup(count($standingGroup->standings));
      foreach ($standingGroup->standings as $standing) {
        $targetGroup->addTicket($indexTickets[$i]);
        $i++;
      }

      $targetGroups[] = $targetGroup;
    }

    //echo "<u>new groups before normalisation</u><br />" . implode("<br />", $targetGroups) . "<br /><br />";
    
    for ($i=0; $i<count($targetGroups); $i++) {
      $targetGroups[$i] = $targetGroups[$i]->normalize();
    }
    
    //echo "<u>new groups after normalisation</u><br />" . implode("<br />", $targetGroups) . "<br /><br />";
    
    return $targetGroups;
  }
  
  public function nextPhase() {
    echo "<pre>";
    $groups = $this->related('groups')->orderByIndex()->withFullStandings()->getWithGroupsAsKey();
    
    $result = array('standings' => array(), 'problems' => array());
  
    // start assigning teams at top of standings list
    $currentStandingsIndex = 0;

    // create a new group for each current group
    $targetGroupIndex = 0;
    foreach ($groups as $sourceGroup) {
      $targetStandings = array();
      $targetSize = count($sourceGroup->standings);

      // repeat the process until the target standings have reached the target
      // size
      while(count($targetStandings) < $targetSize) {
        // get all available teams at current standings index
        $availableStandings = array();
        foreach ($groups as $group) {
          if (isset($group->standings[$currentStandingsIndex])) {
            $availableStandings[] = $group->standings[$currentStandingsIndex];
          }
        }

        // sort available standings by points
        $availableStandings = Standing::sort($availableStandings);
        
        // determine how many standings to assign
        $assignCount = min($targetSize - count($targetStandings), count($availableStandings));
        
        echo "New group " . $targetGroupIndex . ": ";
        foreach ($availableStandings as $t) {
          echo $t->team->name . ', ';
        }
        echo " ($assignCount)";
        echo "\n";
        
        $problem = $this->detectNextPhaseProblems($availableStandings, $currentStandingsIndex, $targetGroupIndex, $assignCount, $groups, $targetSize - count($targetStandings));
        if ($problem !== null) {
          $result['problems'][] = $problem;
        }
        
        // assign the teams and remove them from the standings
        for($i = 0; $i < $assignCount; $i++) {
          $standing = $availableStandings[$i];
          $groups[$standing->group_id]->standings[$currentStandingsIndex] = null;
          //unset($team['group']);
          $targetStandings[] = $standing->team->name;
        }

        // if all available teams of the current standings index have been
        // assigned, increment standings index
        if ($assignCount == count($availableStandings)) {
          $currentStandingsIndex++;
        }

      }

      $targetGroupIndex++;
      // add target standing to result
      $result['standings'][] = $targetStandings;
    }

    //$result = $this->removeNonProblems($result);
    
    
    echo "<pre>";
    print_r($result);
    exit();
    
    return $result;
  }
  
  protected function detectNextPhaseProblems(array $availableStandings, $currentStandingsIndex, $targetGroupIndex, $assignCount, $groups, $spotsRemaining) {
    $lastAssignedStanding = $availableStandings[$assignCount-1];
    $isProblem = false;
    $problem = array('target_group_index' => $targetGroupIndex, 'standing_index' => $currentStandingsIndex, 'teams_needed' => 0, 'sub_problems' => array());
    $problemStandings = array();
    $equalStandingsCount = 0;
    
    // find available standings with the same standings as the last assigned
    // standing, these form a cross-group problem if there are more than th
    // number of standings needed with that amount of points to finish the group
    for ($i = 0; $i < count($availableStandings); $i++) {
      if ($lastAssignedStanding->compareTo($availableStandings[$i]) == 0) {
        $problemStandings[] = $availableStandings[$i];
        if ($i < $assignCount) {
          $problem['teams_needed']++;
        }
      } else {
        $spotsRemaining--;
      }
    }
    
    $problem['spots_remaining'] = $spotsRemaining;

    // there is a problem if the number of teams with the same standing is
    // greater than the number of teams needed to finish the target group
    if (count($problemStandings) > $problem['teams_needed']) {
      //$isProblem = true;
    }

    // for each standing in the cross problem, there might be one or more
    // standings in that same group with an equal standing, thus creating a
    // in-group problem
    foreach ($problemStandings as $problemStanding) {
      $crossProblemTeamGroup = $groups[$problemStanding->group_id];
      $subProblem = array('source_group_id' => $problemStanding->group_id, 'index' => $currentStandingsIndex, 'teams' => array($problemStanding->team->name));
      $equalStandingsCount++;

      // teams with equal standings in same group might end up in the same
      // group, so it's not a problem anymore: these will be removed later
      for ($j = $currentStandingsIndex+1; $j<count($crossProblemTeamGroup->standings); $j++) {
        if ($lastAssignedStanding->compareTo($crossProblemTeamGroup->standings[$j]) == 0) {
          $subProblem['teams'][] = $crossProblemTeamGroup->standings[$j]->team->name;
          $equalStandingsCount++;
          //$isProblem = true;
        }
      }
      $problem['sub_problems'][] = $subProblem;
    }

    $isProblem = $spotsRemaining < $equalStandingsCount && $spotsRemaining > 0;
    
    if (!$isProblem) {
      return null;
    }
    
    return $problem;
  }
  
  protected function removeNonProblems($result) {
    // teams with equal standings in same group might end up in the same group, so it's not a problem anymore
    for ($pIndex = 0; $pIndex < count($result['problems']); $pIndex++) {
      $problem = &$result['problems'][$pIndex];
      
      if (count($problem['sub_problems']) == $problem['teams_needed']) {
        for ($spIndex = 0; $spIndex < count($problem['sub_problems']); $spIndex++) {
          $subProblem = &$problem['sub_problems'][$spIndex];
          $removeSubProblem = true;

          for ($tIndex = 0; $tIndex < count($subProblem['teams']); $tIndex++) {
            $team = &$subProblem['teams'][$tIndex];

            if (!in_array($team, $result['standings'][$problem['target_group_index']])) {
              $removeSubProblem = false;
              break;
            }
          }

          if ($removeSubProblem) {
            array_splice($problem['sub_problems'], $spIndex, 1);
            $spIndex--;
          }

        }
      }
      
      if (count($problem['sub_problems']) == 0) {
        array_splice($result['problems'], $pIndex, 1);
        $pIndex--;
      }
    }
    
    return $result;
  }
}