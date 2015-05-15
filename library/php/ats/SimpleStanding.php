<?php

namespace ats;

class SimpleStanding implements Standing {
    public $name;
    public $points;
    
    public function __construct($name, $points) {
        $this->name = $name;
        $this->points = $points;
    }
    
    public function compareTo(Standing $standing) {
        return $this->points - $standing->points;
    }
    
    public function equals(Standing $standing) {
        return $this->name == $standing->name;
    }
    
    public function __toString() {
        return $this->name;
    }
}