<?php

namespace modules\scheduler\forms\tournamentfield;

class ModifyForm extends \ultimo\form\Form {
  
  protected function init() {
    $this->appendValidator('field_id', 'InArray', array(array_keys($this->getConfig('availableFields'))));
  }
}