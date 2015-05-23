<?php

namespace modules\scheduler;

class Module extends \ultimo\mvc\Module implements \ultimo\security\mvc\AuthorizedModule {
  public function getAcl() {
    $acl = new \ultimo\security\Acl();
    $acl->addRole('scheduler.guest');
    $acl->addRole('scheduler.admin');
    
    $acl->addRole('guest', array('scheduler.guest'));
    $acl->addRole('scheduler', array('scheduler.admin'));
    
    $acl->allow('scheduler.guest', array('match.dashboard'));
    $acl->allow('scheduler.admin');
    return $acl;
  }

}