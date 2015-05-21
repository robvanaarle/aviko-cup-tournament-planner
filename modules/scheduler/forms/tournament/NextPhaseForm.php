<?php

namespace modules\scheduler\forms\tournament;

class NextPhaseForm extends \ultimo\form\Form {
  
  protected function init() {
    $this->appendValidator('name', 'StringLength', array(1, 255));
    
    $this->appendValidator('starts_at', 'NotEmpty');
    $this->appendValidator('starts_at', 'Date', array('Y-m-d H:i:s'));
  }
}