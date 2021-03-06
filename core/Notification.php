<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';
/** notification manager*/
class Notification extends MIDAS_Notification
  {
  public $_components = array('Utility', 'Authentication');
  public $_models = array('User', 'Item');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDasboard');
    $this->addCallBack('CALLBACK_CORE_GET_CONFIG_TABS', 'getConfigTabs');
    $this->addCallBack('CALLBACK_CORE_PASSWORD_CHANGED', 'setDefaultWebApiKey');
    $this->addCallBack('CALLBACK_CORE_NEW_USER_ADDED', 'setDefaultWebApiKey');
    $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
    $this->addCallBack('CALLBACK_CORE_PARAMETER_AUTHENTICATION', 'tokenAuth');
    }//end init

  /** generate dashboard information */
  public function getDasboard()
    {
    $return = array();
    $return['Config Folder Writable'] = array(is_writable(LOCAL_CONFIGS_PATH));
    $return['Data Folder Writable'] = array(is_writable(UtilityComponent::getDataDirectory()));
    // pass in empty string since we want to check the overall root temp directory
    $return['Temporary Folder Writable'] = array(is_writable(UtilityComponent::getTempDirectory('')));

    return $return;
    }//end _getDasboard

  /** get config Tabs */
  public function getConfigTabs($params)
    {
    $user = $params['user'];
    $fc = Zend_Controller_Front::getInstance();
    $webroot = $fc->getBaseUrl();
    return array('API' => $webroot.'/apikey/usertab?userId='.$user->getKey());
    }

  /** Reset the user's default web API key */
  public function setDefaultWebApiKey($params)
    {
    if(!isset($params['userDao']))
      {
      throw new Zend_Exception('Error: userDao parameter required');
      }
    $userApiModel = MidasLoader::loadModel('Userapi');
    $userApiModel->createDefaultApiKey($params['userDao']);
    }

  /**
   * If a user is deleted, we should delete their api keys
   * @param userDao the user dao that is about to be deleted
   */
  public function handleUserDeleted($params)
    {
    if(!isset($params['userDao']))
      {
      throw new Zend_Exception('Error: userDao parameter required');
      }
    $userApiModel = MidasLoader::loadModel('Userapi');
    $apiKeys = $userApiModel->getByUser($params['userDao']);

    foreach($apiKeys as $apiKey)
      {
      $userApiModel->delete($apiKey);
      }
    }

  /**
   * When we redirect from the web api for downloads, we add the user's token as a parameter,
   * and the controller makes a callback to this module to get the user.
   */
  public function tokenAuth($params)
    {
    $token = $params['authToken'];
    return $this->Component->Authentication->getUser(array('token' => $token), null);
    }
  } // end class
