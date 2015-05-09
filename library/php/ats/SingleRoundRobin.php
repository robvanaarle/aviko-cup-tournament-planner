<?php

namespace ats;

class SingleRoundRobin {
  protected $teams;
  
  public function __construct(array $teams) {
    $this->teams = array_values($teams);
  }
  
  public function getNumberOfTeams() {
    return count($this->teams);
  }
  
  public function getNumberOfMatches() {
    $teamCount = $this->getNumberOfTeams();
    return $teamCount / 2 * ($teamCount - 1);
  }
  
  public function getNumberOfRounds() {
    $teamCount = $this->getNumberOfTeams();
    if ($teamCount % 2 == 0) {
      return $teamCount-1;
    } else {
      return $teamCount;
    }
  }
  
  public function generateSchedule() {
    // http://en.wikipedia.org/wiki/Round-robin_tournament
    $schedule = array();
    $teamCount = $this->getNumberOfTeams();
    $teams = $this->teams;
    
    // add dummy team to indicate a team does not play
    if ($teamCount % 2 == 1) {
      $teams[] = null;
      $teamCount++;
    }
    
    // reverse to fix first team as last element of array, this makes the
    // rotation of the other teams easier
    $teams = array_reverse($teams);
    
    $roundCount = $this->getNumberOfRounds();
    $halfTeamCount = $teamCount/2;
    $maxRotatingTeamIndex = $teamCount-1;
    for ($roundIndex = 0; $roundIndex < $roundCount; $roundIndex++) {
      $roundMatches = array();
      
      // assign competitors
      for ($j = 0; $j < $halfTeamCount; $j++) {
        
        if ($j == 0) {
          // fixed team
          $teamAIndex = $maxRotatingTeamIndex;
        } else {
          $teamAIndex = ($maxRotatingTeamIndex - $j + $roundIndex) % $maxRotatingTeamIndex;
        }
        
        $teamBIndex = ($roundIndex + $j) % $maxRotatingTeamIndex;
        
        // alternate home/away
        if ($roundIndex % 2 == 1) {
          $tempTeamIndex = $teamAIndex;
          $teamAIndex = $teamBIndex;
          $teamBIndex = $tempTeamIndex;
        }
        
        $homeTeam = $teams[$teamAIndex];
        $awayTeam = $teams[$teamBIndex];
        
        // discard match against dummy team
        if ($homeTeam === null || $awayTeam === null) {
          continue;
        }
        
        // create match
        $roundMatches[] = array('home' => $homeTeam, 'away' => $awayTeam);
      }

      $schedule[] = $roundMatches;
    }
    
    return $schedule;
  }
}