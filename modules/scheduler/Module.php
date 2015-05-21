<?php

namespace modules\scheduler;

class Module extends \ultimo\mvc\Module implements \ultimo\security\mvc\AuthorizedModule {
  public function getAcl() {
    $acl = new \ultimo\security\Acl();
    $acl->addRole('guest');
    $acl->addRole('scheduler');
    
    $acl->allow('guest', array('match.dashboard'));
    $acl->allow('scheduler');
    return $acl;
  }

}