<?php

class Ldap_ConfigController extends Ldap_AppController
{
   public $_moduleForms=array('Config');
   public $_components=array('Utility');

   function indexAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
      
    if(file_exists(BASE_PATH."/core/configs/ldap.local.ini"))
      {
      $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/ldap.local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/ldap/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();
    
    $formArray = $this->getFormAsArray($configForm);    
    $formArray['hostname']->setValue($applicationConfig['global']['ldap.hostname']);
    $formArray['basedn']->setValue($applicationConfig['global']['ldap.basedn']);
    $formArray['protocolVersion']->setValue($applicationConfig['global']['ldap.protocolVersion']);
    $formArray['search']->setValue($applicationConfig['global']['ldap.search']);
    $formArray['proxyBasedn']->setValue($applicationConfig['global']['ldap.proxyBasedn']);
    $formArray['proxyPassword']->setValue($applicationConfig['global']['ldap.proxyPassword']);
    $formArray['autoAddUnknownUser']->setValue($applicationConfig['global']['ldap.autoAddUnknownUser']);
    $formArray['bindn']->setValue($applicationConfig['global']['ldap.bindn']);
    $formArray['bindpw']->setValue($applicationConfig['global']['ldap.bindpw']);
    $formArray['backup']->setValue($applicationConfig['global']['ldap.backup']);
    $this->view->configForm = $formArray;
    
    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(BASE_PATH."/core/configs/ldap.local.ini.old"))
          {
          unlink(BASE_PATH."/core/configs/ldap.local.ini.old");
          }
        if(file_exists(BASE_PATH."/core/configs/ldap.local.ini"))
          {
          rename(BASE_PATH."/core/configs/ldap.local.ini",BASE_PATH."/core/configs/ldap.local.ini.old");
          }
        $applicationConfig['global']['ldap.hostname'] = $this->_getParam('hostname');
        $applicationConfig['global']['ldap.basedn'] = '"'.$this->_getParam('basedn').'"';
        $applicationConfig['global']['ldap.protocolVersion'] = $this->_getParam('protocolVersion');
        $applicationConfig['global']['ldap.search'] = $this->_getParam('search');
        $applicationConfig['global']['ldap.proxyBasedn'] = $this->_getParam('proxyBasedn');
        $applicationConfig['global']['ldap.autoAddUnknownUser'] = $this->_getParam('autoAddUnknownUser');
        $applicationConfig['global']['ldap.useActiveDirectory'] = $this->_getParam('useActiveDirectory');
        $applicationConfig['global']['ldap.bindn'] = '"'.$this->_getParam('bindn').'"';
        if(isset($this->_getParam('bindpw')))
          {
          $applicationConfig['global']['ldap.bindpw'] = $this->_getParam('bindpw');
          }        
        if(isset($this->_getParam('proxyPassword')))
          {
          $applicationConfig['global']['ldap.proxyPassword'] = $this->_getParam('proxyPassword');
          }        
        $applicationConfig['global']['ldap.backup'] = $this->_getParam('backup');
        $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/ldap.local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    } 
    
}//end class