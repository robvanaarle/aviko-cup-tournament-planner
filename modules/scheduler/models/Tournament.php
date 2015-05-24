<?php

namespace modules\scheduler\models;

class Tournament extends \ucms\tournamentplanner\models\Tournament {
  
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
    //$fields = $this->relatedFields()->all();
    $fields = array();
    foreach ($this->relatedFields()->all() as $field) {
      $fields[$field->id] = $field;
    }
    
    
    
    $fieldIndex = 0;
    $availableFields = $fields;
    
    $teamsThisTimeslot = array();
    $fieldCurrentMatches = array();
    $fieldPrevMatches = array();
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
            $availableFields = $fields;
            $teamsThisTimeslot = array();
            $fieldPrevMatches = $fieldCurrentMatches;
            $fieldCurrentMatches = array();
            $startsAt->add($duration);
          }
          
          $matchObject = $this->_manager->Match->create();
          $matchObject->home_team_id = $match['home']->id;
          $matchObject->away_team_id = $match['away']->id;
          
          // check if a team played previous round, and if so, use that field
          $field = null;

          foreach ($fieldPrevMatches as $fieldId => $prevMatch) {
            if ($prevMatch->involvesTeamId($match['home']->id) || $prevMatch->involvesTeamId($match['away']->id)) {
              $field = $fields[$prevMatch->field_id];
              
              //echo "field {$field->id}<br />";
              
              // check if that field is available this timeslot
              if (isset($fieldCurrentMatches[$field->id])) {
                // swap the field in use with the next available field
                $vals = array_values($availableFields); $swapField = $vals[0];
                unset($availableFields[$swapField->id]);
                
                $swapMatch = $fieldCurrentMatches[$field->id];
                unset($fieldCurrentMatches[$field->id]);
                
                $swapMatch->field_id = $swapField->id;
                $fieldCurrentMatches[$swapField->id] = $swapMatch;
              } else {
                unset($availableFields[$field->id]);
              }
              break;
            }
          }
          
          // if no previous field has been found, take next available
          if ($field == null) {
            $vals = array_values($availableFields); $field = $vals[0];
            unset($availableFields[$field->id]);
          }
          
          $matchObject->field_id = $field->id;
          $teamFields['current'][$matchObject->home_team_id] = $matchObject->field_id;
          $teamFields['current'][$matchObject->away_team_id] = $matchObject->field_id;
          $fieldCurrentMatches[$field->id] = $matchObject;
          
          $matchObject->starts_at = $startsAt->format("Y-m-d H:i:s");
          $matchObject->group_id = $groupId;
          $matchObject->referee = '';
          $matchObjects[] = $matchObject;
          
          //echo "match {$matchIndex} of round {$roundIndex} for group {$groupId} at field {$fieldIndex}<br />";
          
          $teamsThisTimeslot[] = $match['home']->id;
          $teamsThisTimeslot[] = $match['away']->id;
          $fieldIndex++;
          if (count($availableFields) == 0) {
            $availableFields = $fields;
            $teamsThisTimeslot = array();
            $fieldPrevMatches = $fieldCurrentMatches;
            $fieldCurrentMatches = array();
            $startsAt->add($duration);
          }
        }
      }
    }

    $this->_manager->Match->multiInsert($matchObjects);
  }
  
  public function getNextPhaseTicketGroups() {
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
  
  public function createNextPhaseTournament($name, $starts_at) {
    // create the new tournament
    $newTournament = $this->_manager->create('Tournament');
    $newTournament->name = $name;
    $newTournament->match_duration = $this->match_duration;
    $newTournament->between_duration = $this->between_duration;
    $newTournament->starts_at = $starts_at;
    $newTournament->show_in_dashboard = false;
    $newTournament->save();
    
    // copy the fields
    $fields = $this->relatedFields()->all();
    foreach ($fields as $field) {
      $newTournamentField = $this->_manager->create('TournamentField');
      $newTournamentField->tournament_id = $newTournament->id;
      $newTournamentField->field_id = $field->id;
      $newTournamentField->save();
    }
    
    // fetch the old groups, so those names can be reused.
    $groups = $this->related('groups')->orderByIndex()->all();
    
    // create a new group for each ticket group and add all teams of tickets
    // that represent a single team
    $ticketGroups = $this->getNextPhaseTicketGroups();
    foreach ($ticketGroups as $index => $ticketGroup) {
      $newGroup = $this->_manager->create('Group');
      $newGroup->name = $groups[$index]->name;
      $newGroup->tournament_id = $newTournament->id;
      $newGroup->save();
      
      foreach ($ticketGroup->tickets as $ticket) {
        if (!$ticket->representsSingleTeam()) {
          continue;
        }
        $newGroupTeam = $this->_manager->create('GroupTeam');
        $newGroupTeam->group_id = $newGroup->id;
        $newGroupTeam->team_id = $ticket->subTickets[0]->standings[0]->team->id;
        $newGroupTeam->save();
      }
      
      // create standing
      $newGroup->syncStandings();
    }

    return $newTournament;
  }
}