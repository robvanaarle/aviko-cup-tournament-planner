<?php

namespace ats;

class Decision {
  public $standings = array();
  
  public function count() {
    return count($this->standings);
  }
  
  public function equals(Decision $decision) {
    if ($this->count() != $decision->count()) {
      return false;
    }
    
    $haystack = $decision->standings;
    
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
  
  public function __toString() {
    return implode(" v ", $this->standings);
  }
  
}