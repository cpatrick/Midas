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

/**
 *  License controller
 */
class LicenseController extends AppController
  {

  public $_models = array('License');
  public $_daos = array('License');
  public $_components = array();
  public $_forms = array();

  /**
   * Init Controller
   */
  function init()
    {
    }

  /**
   * Index action. Lists all licenses on the admin page
   */
  function indexAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->view->licenses = $this->License->getAll();
    }

  /** View the license text in a dialog */
  function viewAction()
    {
    $this->disableLayout();
    $licenseId = $this->_getParam('licenseId');

    if(!isset($licenseId))
      {
      throw new Zend_Exception('Must pass a license id');
      }
    $license = $this->License->load($licenseId);
    if($license == false)
      {
      throw new Zend_Exception('Invalid licenseId');
      }
    $this->view->license = $license;
    }

  /** Delete a license */
  function deleteAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $licenseId = $this->_getParam('licenseId');

    $license = $this->License->load($licenseId);
    if($license == false)
      {
      throw new Zend_Exception('Invalid licenseId');
      }
    $this->License->delete($license);

    echo JsonComponent::encode(array(true, 'Success stub'));
    }

  /** Save an existing license */
  function saveAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $licenseId = $this->_getParam('licenseId');
    if(!isset($licenseId))
      {
      throw new Zend_Exception('Must pass a licenseId parameter');
      }
    $license = $this->License->load($licenseId);
    if($license == false)
      {
      throw new Zend_Exception('Invalid licenseId');
      }
    $name = $this->_getParam('name');
    $fulltext = $this->_getParam('fulltext');

    $license->setName($name);
    $license->setFulltext($fulltext);
    $this->License->save($license);
    echo JsonComponent::encode(array(true, 'Changes saved'));
    }

  /** Create a new license */
  function createAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $name = $this->_getParam('name');
    $fulltext = $this->_getParam('fulltext');

    $license = new LicenseDao();
    $license->setName($name);
    $license->setFulltext($fulltext);
    $this->License->save($license);
    echo JsonComponent::encode(array(true, 'Created new license'));
    }
}
