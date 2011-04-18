<?php

/**
 *  AJAX request for the admin Controller
 */
class AdminController extends AppController
{
  public $_models=array('Errorlog');
  public $_daos=array();
  public $_components=array('Upgrade','Utility');
  public $_forms=array('Admin');
    
  /** index*/
  function indexAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    $this->view->header="Administration";
    $configForm=$this->Form->Admin->createConfigForm();
    
    $applicationConfig=parse_ini_file (BASE_PATH.'/core/configs/application.local.ini',true);
    $formArray=$this->getFormAsArray($configForm);
    
    $formArray['name']->setValue($applicationConfig['global']['application.name']);
    $formArray['environment']->setValue($applicationConfig['global']['environment']);
    $formArray['lang']->setValue($applicationConfig['global']['application.lang']);
    $formArray['smartoptimizer']->setValue($applicationConfig['global']['smartoptimizer']);
    $formArray['timezone']->setValue($applicationConfig['global']['default.timezone']);
    $this->view->configForm=$formArray;
    
    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig=$this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        $applicationConfig=parse_ini_file (BASE_PATH.'/core/configs/application.local.ini',true);
        if(file_exists( BASE_PATH.'/core/configs/application.local.ini.old'))
          {
          unlink( BASE_PATH.'/core/configs/application.local.ini.old');
          }
        rename(BASE_PATH.'/core/configs/application.local.ini', BASE_PATH.'/core/configs/application.local.ini.old');
        $applicationConfig['global']['application.name']=$this->_getParam('name');
        $applicationConfig['global']['application.lang']=$this->_getParam('lang');
        $applicationConfig['global']['environment']=$this->_getParam('environment');
        $applicationConfig['global']['smartoptimizer']=$this->_getParam('smartoptimizer');
        $applicationConfig['global']['default.timezone']=$this->_getParam('timezone');
        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/application.local.ini', $applicationConfig);
        echo JsonComponent::encode(array(true,'Changed saved'));
        }
      }
    }//end indexAction
    
 
  /** show logs*/
  function showlogAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }
    $this->_helper->layout->disableLayout();
    
    $start=$this->_getParam("startlog");
    $end=$this->_getParam("endlog");
    $module=$this->_getParam("modulelog");
    $priority=$this->_getParam("prioritylog");
    if(!isset($start))
      {
      $start=date('c',strtotime("-24 hour"));
      }
    else
      {
      $start=date('c',  strtotime($start));
      }
    if(!isset($end))
      {
      $end= date('c');
      }
    else
      {
      $end=date('c',  strtotime($end));
      }
    if(!isset($module))
      {
      $module='all';
      }
    if(!isset($priority))
      {
      $priority='all';
      }
      
    $logs=$this->Errorlog->getLog($start, $end,$module,$priority);
    foreach ($logs as $key=>$log)
      {
      $logs[$key]=$log->_toArray();
      if(substr($log->getMessage(), 0, 5)=='Fatal')
        {
        $shortMessage=substr($log->getMessage(), strpos($log->getMessage(), "[message]")+10,40);
        }
      elseif(substr($log->getMessage(), 0, 6)=='Server')
        {
        $shortMessage=substr($log->getMessage(), strpos($log->getMessage(), "Message:")+9,40);
        }
      else
        {
        $shortMessage=substr($log->getMessage(), 0,40);
        }
      $logs[$key]['shortMessage']=$shortMessage.' ...';
      }
    $this->view->jsonLogs=JsonComponent::encode($logs);
    $this->view->jsonLogs=htmlentities($this->view->jsonLogs);
    
    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      echo $this->view->jsonLogs;
      return;
      }
      
    $modulesConfig=Zend_Registry::get('configsModules');
      
    $modules=array('all','core');
    foreach($modulesConfig as $key=>$module)
      {
      $modules[]=$key;
      }    
    $this->view->modulesLog=$modules;
    }//showlogAction
    
  /** upgrade database*/
  function upgradeAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }
    $this->_helper->layout->disableLayout();

    $db=Zend_Registry::get('dbAdapter');
    $dbtype=Zend_Registry::get('configDatabase')->database->adapter;
    $modulesConfig=Zend_Registry::get('configsModules');
    
    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $upgraded=false;
      $modulesConfig=Zend_Registry::get('configsModules');
      $modules=array();
      foreach($modulesConfig as $key=>$module)
        {
        $this->Component->Upgrade->init($key,$db,$dbtype);
        $upgraded=$upgraded||$this->Component->Upgrade->upgrade($module->version);
        }    
      $this->Component->Upgrade->init('core',$db,$dbtype);
      $upgraded=$upgraded||$this->Component->Upgrade->upgrade(Zend_Registry::get('configDatabase')->version);
      $this->view->upgraded=$upgraded;
      
      $dbtype=Zend_Registry::get('configDatabase')->database->adapter;
      $modulesConfig=Zend_Registry::get('configsModules');
      if($upgraded)
        {
        echo JsonComponent::encode(array(true,'Upgraded'));
        }
      else
        {
        echo JsonComponent::encode(array(true,'Nothing to upgrade'));
        }
      return;
      }
      
    $modules=array();
    foreach($modulesConfig as $key=>$module)
      {
      $this->Component->Upgrade->init($key,$db,$dbtype);
      $modules[$key]['target']=$this->Component->Upgrade->getNewestVersion();
      $modules[$key]['targetText']=$this->Component->Upgrade->getNewestVersion(true);
      $modules[$key]['currentText']=$module->version;
      $modules[$key]['current']=$this->Component->Upgrade->transformVersionToNumeric($module->version);
      }      
   
    $this->view->modules=$modules;
    
    $this->Component->Upgrade->init('core',$db,$dbtype);
    $core['target']=$this->Component->Upgrade->getNewestVersion();
    $core['targetText']=$this->Component->Upgrade->getNewestVersion(true);
    $core['currentText']=Zend_Registry::get('configDatabase')->version;
    $core['current']=$this->Component->Upgrade->transformVersionToNumeric(Zend_Registry::get('configDatabase')->version);
    $this->view->core=$core;
    }//end upgradeAction
    
  /**
   * \fn serversidefilechooser()
   * \brief called by the server-side file chooser
   */
  function serversidefilechooserAction()
    {
    /*$userid = $this->CheckSession();
    if (!$this->User->isAdmin($userid))
      {
      echo "Administrative privileges required";
      exit ();
      }
      */
    
    // Display the tree
    $_POST['dir'] = urldecode($_POST['dir']);
    $files = array();
    if( strpos( strtolower(PHP_OS), 'win') !== false )
      {
      $files = array();
      for($c='A'; $c<='Z'; $c++)
        {
        if(is_dir($c . ':'))
          {
          $files[] = $c . ':';
          }
        }
      }
    else
      {
      $files[] = '/';
      }

    if( file_exists($_POST['dir']) || file_exists($files[0]) ) 
      {
      if(file_exists($_POST['dir']))
        {
        $files = scandir($_POST['dir']);
        }
      natcasesort($files);
      echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
      foreach( $files as $file ) 
        {
        if( file_exists( $_POST['dir'] . $file) && $file != '.' && $file != '..' && is_readable($_POST['dir'] . $file) )
          {
          if( is_dir($_POST['dir'] . $file) )
            {
            echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";  
            }
          else // not a directory: a file!
            {
            $ext = preg_replace('/^.*\./', '', $file); 
            echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "\">" . htmlentities($file) . "</a></li>";
            }              
          }
        }
      echo "</ul>"; 
      }
    else
      {
      echo "File ".$_POST['dir']." doesn't exist";
      }     
    // No views  
    exit();
    } // end function  serversidefilechooserAction
    
    
    
    
    
} // end class

  