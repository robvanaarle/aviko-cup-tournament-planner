<?php

namespace modules\scheduler;

class Module extends \ultimo\mvc\Module { //implements \ultimo\security\mvc\AuthorizedModule {
  public function getAcl() {
    $acl = new \ultimo\security\Acl();
    $acl->addRole('guest');
    $acl->addRole('admin');
    
    //$acl->allow('guest', array('category.index'));
    $acl->allow('admin');
    return $acl;
  }

}