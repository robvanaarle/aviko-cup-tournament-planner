<?php

namespace ats;

class SubTicket {
  public $assignIndex = 0;
  public $standings = array();
  public $group;
  
  public function __construct($assignIndex, $group) {
    $this->assignIndex = $assignIndex;
    $this->group = $group;
  }
  
  public function addStanding(Standing $standings) {
    $this->standings[] = $standings;
  }
  
  public function count() {
    return count($this->standings);
  }
  
  public function equals(SubTicket $subTicket) {
    return $this->assignIndex == $subTicket->assignIndex && $this->hasEqualTeams($subTicket);
  }
  
  public function hasEqualTeams(SubTicket $subTicket) {
    if ($this->count() != $subTicket->count()) {
      return false;
    }
    
    $haystack = $subTicket->standings;
    
    foreach ($this->standings as $needle) {
      foreach ($haystack as $index => $compare) {
        if ($needle->equals($compare)) {
          unset($haystack[$index]);
          continue 2;
        }
      }
      return false;
    }
    
    return true;
  }
  
  public function getMutations() {
    $mutations = array();
    for ($assignIndex = 0; $assignIndex < $this->count(); $assignIndex++) {
      $subTicket = clone $this;
      $subTicket->assignIndex = $assignIndex;
      $mutations[] = $subTicket;
    }
    return $mutations;
  }
  
  public function __toString() {
    //if ($this->count() == 0) {
    //  return '[]^' . ($this->assignIndex+1);
    //} elseif ($this->count() == 1) {
     //   return $this->teams[0];
    //}
    
    $teams = $this->standings;
    if (is_array($teams[0])) {
      for ($i=0; $i<$this->count(); $i++) {
        $teams[$i] = $teams[$i]['name'];
      }
    }
    
    return '(' . implode(' v ', $teams) . ')^' . ($this->assignIndex+1);
  }
}