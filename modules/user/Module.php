<?php

namespace modules\user;

class Module extends \ultimo\mvc\Module implements \ultimo\security\mvc\AuthorizedModule {
  
  public function init() {
    $this->setParent($this->application->getModule('\ucms\user'));
  }
  
  public function getAcl() {
    $acl = $this->getParent()->getAcl();

    $acl->addRole('guest', array('user.guest'));
    $acl->addRole('scheduler', array('user.member'));
    $acl->addRole('admin', array('user.admin'));

    return $acl;
  }
  
}