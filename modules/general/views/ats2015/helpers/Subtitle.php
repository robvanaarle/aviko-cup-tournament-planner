<?php

namespace modules\general\views\ats2015\helpers;

class Subtitle extends \ultimo\phptpl\mvc\Helper {
  protected $breadcrumbs = array();
  
  public function __invoke($text=null, $location=null) {
    if ($text !== null) {
      if ($location === null) {
        $this->breadcrumbs[] = $this->engine->escape($text);
      } else {
        $this->breadcrumbs[] = "<a href=\"{$location}\">" . $this->engine->escape($text) . "</a>";
      }
    }
    return $this;
  }
  
  public function __toString() {
    return implode(" &raquo ", $this->breadcrumbs);
  }
}