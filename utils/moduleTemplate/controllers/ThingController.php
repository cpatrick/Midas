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

/** Controller template for the @MN@ module */
class @MN_CAP@_ThingController extends @MN_CAP@_AppController
  {
  public $_models = array();
  public $_moduleModels = array();

  /** STUB: Example get action */
  function getAction()
    {
    $id = $this->_getParam('id');
    $this->view->id = $id;
    }

  /** STUB: Example create action */
  function createAction()
    {
    $this->disableLayout();
    $this->disableView();
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Done'));
    }

  /** STUB: Example update action */
  function updateAction()
    {
    $this->disableLayout();
    $this->disableView();
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Done'));
    }

  /** STUB: Example delete action */
  function deleteAction()
    {
    $this->disableLayout();
    $this->disableView();
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Done'));
    }
  }
