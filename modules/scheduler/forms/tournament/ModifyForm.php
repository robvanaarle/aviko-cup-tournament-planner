<?php

namespace modules\scheduler\forms\tournament;

class ModifyForm extends \ultimo\form\Form {
  
  protected function init() {
    $this->appendValidator('name', 'StringLength', array(1, 255));
    
    $this->appendValidator('match_duration', 'NotEmpty');
    $this->appendValidator('match_duration', 'NumericValue', array(1, 60, true));
    
    $this->appendValidator('between_duration', 'NotEmpty');
    $this->appendValidator('between_duration', 'NumericValue', array(1, 60, true));
    
    $this->appendValidator('starts_at', 'NotEmpty');
    $this->appendValidator('starts_at', 'Date', array('Y-m-d H:i:s'));
  }
}