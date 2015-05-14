<?php

namespace modules\scheduler\forms\match;

class ModifyForm extends \ultimo\form\Form {
  
  protected function init() {
    $this->setAllowedEmpty('goals_home');
    $this->appendValidator('goals_home', 'NumericValue', array(0, 100, true));
    
    $this->setAllowedEmpty('goals_away');
    $this->appendValidator('goals_away', 'NumericValue', array(0, 100, true));
    
    $this->appendValidator('starts_at', 'NotEmpty');
    $this->appendValidator('starts_at', 'Date', array('Y-m-d H:i:s'));
    
    $this->appendValidator('field_id', 'InArray', array(array_keys($this->getConfig('availableFields'))));
  }
}