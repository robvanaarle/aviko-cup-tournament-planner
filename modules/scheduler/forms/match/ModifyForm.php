<?php

namespace  modules\scheduler\forms\match;

class ModifyForm extends \ultimo\form\Form {
  
  protected function init() {
    $this->appendValidator('goals_home', 'NotEmpty');
    $this->appendValidator('goals_away', 'NotEmpty');
  }
}