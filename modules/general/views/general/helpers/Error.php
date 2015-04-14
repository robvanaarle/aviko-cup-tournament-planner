<?php

namespace modules\general\views\general\helpers;

class Error extends \ultimo\phptpl\mvc\Helper {
  protected $translator;
  
  public function __invoke(\ultimo\form\Form $form, $field) {
    if ($this->translator === null) {
      $this->translator = new \ultimo\validation\translate\Translator($this->module->getPlugin('translator'));
    }
    $messages = $form->getErrorMessages($field, $this->translator);
    if (empty($messages)) {
      return '';
    }
    return '<ul class="errors"><li>' . $this->engine->escape($messages[0]) . '</li></ul>';
  }
}