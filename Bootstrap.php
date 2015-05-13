<?php

use ultimo\mvc\routers\rules\DynamicRule;
use ultimo\mvc\routers\rules\StaticRule;
class Bootstrap extends \ultimo\mvc\Bootstrap implements \ultimo\mvc\plugins\ApplicationPlugin {
  public function run() {
    $theme = 'ats2015';
    // router
    $this->initRoutes();
    
    $this->application->getPlugin('viewRenderer')->setTheme($theme);
    
    // ErrorHandler
    $errorHandler = new \ultimo\mvc\plugins\ErrorHandler();
    $errorHandler->setDebugErrorHandler($this->application->getRegistry('ultimo.debug.error.ErrorHandler'));
    $this->application->addPlugin($errorHandler);
    
    // Orm
    $uormPlugin = new \ultimo\orm\mvc\plugins\OrmManagers();
    $uormPlugin->addGlobalModel('`user_user`', 'User', '\ucms\user\models');
    
    if ($this->application->getEnvironment() == 'development') {
      $uormPlugin->addConnection('master', 'mysql:dbname=aviko;host=127.0.0.1', 'root');
    } else {
      
    }
    
    $this->application->addPlugin($uormPlugin, 'uorm');
    $this->application->addPlugin($this);
    
    // FileTranslator
    $translator = new \ultimo\translate\mvc\plugins\FileTranslator($this->application, 'nl');
    $translator->setAvailableLocales(array('nl'));
    $this->application->addPlugin($translator);
    
    // Locale
    $localesPlugin = new \ultimo\util\locale\mvc\plugins\Locale('nl');
    $localesPlugin->getFormatter()->dateTimeZone = new DateTimeZone('Europe/Amsterdam');
    $this->application->addPlugin($localesPlugin, 'locale');
    
    // Acl
    $acl = new \ultimo\security\Acl();
    $acl->addRole('guest');
    $acl->addRole('admin', array('guest'));
    $guestUser = new \ucms\user\models\User();
    $guestUser->id = 0;
    $guestUser->role = 'guest';
    $this->application->addPlugin(new \ultimo\security\mvc\plugins\Authorizer($guestUser, $acl), 'authorizer');
    
    // Config
    $this->application->addPlugin(new \ultimo\util\config\mvc\plugins\FileConfigPlugin('\ultimo\util\config\IniConfig', 'ini'));
    
    // FormBroker
    $this->application->addPlugin(new \ultimo\form\mvc\FormsPlugin($theme));
    
  }
  
  public function initRoutes() {
    $router = $this->application->getRouter();
    
    $router->addRule('general.frontpage.index', new StaticRule('', array(
        'module' => 'general',
        'controller' => 'frontpage',
        'action' => 'index'
    )));
  }
  
  public function onPluginAdded(\ultimo\mvc\Application $application) { }
  
  public function onModuleCreated(\ultimo\mvc\Module $module) {
    $fileMTimeCache = new \ultimo\io\FileModifyTimeCache(
      new \ultimo\util\cache\LockFileCache($this->application->getApplicationDir() . '/cache/mediaversions')
    );
    $fileMTimeCache->appendBasePath($this->application->getApplicationDir() . DIRECTORY_SEPARATOR . 'public');
    
    $env = $this->application->getEnvironment();
    if ($env == 'development') {
      $fileMTimeCache->setTtl(1);
    }
    
    $module->getView()->addDecoratorClass('HeadLink', 'ultimo\phptpl\helpers\decorators\HeadLinkFileCache', array('fileMTimeCache' => $fileMTimeCache));
    $module->getView()->addDecoratorClass('HeadLink', 'ultimo\phptpl\helpers\decorators\scssphp\Scssphp', array(
      'scssphpPath' => 'library/php/scssphp/scss.inc.php',
      'compiledPath' => 'assets/compiled/css',
      'fileMTimeCache' => $fileMTimeCache,
      'extensions' => array('scss', 'css'),
      'formatter' => $env == 'development' ? null : 'scss_formatter_compressed'
    ));
    $module->getView()->addDecoratorClass('HeadLink', 'ultimo\phptpl\mvc\helpers\decorators\HeadLinkTheme');
    
    /*$module->getView()->addDecoratorClass('HeadScript', 'ultimo\phptpl\helpers\decorators\HeadScriptFileCache', array('fileMTimeCache' => $fileMTimeCache));
    $module->getView()->addDecoratorClass('HeadScript', 'ultimo\phptpl\helpers\decorators\mrclay\JSMin', array(
      'compiledPath' => 'assets/compiled/js',
      'fileMTimeCache' => $fileMTimeCache,
      'minifierType' => $env == 'development' ? 'none' : 'jsmin'
    ));*/
    $module->getView()->addDecoratorClass('HeadScript', 'ultimo\phptpl\mvc\helpers\decorators\HeadScriptTheme');
  }
  
  public function onRoute(\ultimo\mvc\Application $application, \ultimo\mvc\Request $request) {
    //$request->setBasePath('/test/');
  }
  
  public function onRouted(\ultimo\mvc\Application $application, \ultimo\mvc\Request $request=null) { }
  
  public function onDispatch(\ultimo\mvc\Application $application) { }
  
  public function onDispatched(\ultimo\mvc\Application $application) { }
  
}