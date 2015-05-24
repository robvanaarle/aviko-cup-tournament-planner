<?php

namespace modules\scheduler;

class Module extends \ultimo\mvc\Module implements \ultimo\security\mvc\AuthorizedModule {
  
  public function init() {
    $this->setParent($this->application->getModule('\ucms\tournamentplanner'));
  }
  
  public function getAcl() {
    $acl = $this->getParent()->getAcl();

    $acl->addRole('guest', array('tournamentplanner.guest'));
    $acl->addRole('scheduler', array('tournamentplanner.admin'));

    return $acl;
  }

}