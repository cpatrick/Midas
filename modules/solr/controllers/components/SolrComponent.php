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

/** Component for accessing the solr server */
class Solr_SolrComponent extends AppComponent
  {
  /**
   * Returns the Apache_Solr_Service object.
   */
  public function getSolrIndex()
    {
    $settingModel = MidasLoader::loadModel('Setting');
    $solrHost = $settingModel->getValueByName('solrHost', 'solr');
    $solrPort = $settingModel->getValueByName('solrPort', 'solr');
    $solrWebroot = $settingModel->getValueByName('solrWebroot', 'solr');

    if($solrHost === null)
      {
      throw new Zend_Exception('Solr settings not saved');
      }
    require_once BASE_PATH.'/modules/solr/library/Apache/Solr/Service.php';
    require_once BASE_PATH.'/modules/solr/library/Apache/Solr/Document.php';

    return new Apache_Solr_Service($solrHost, (int)$solrPort, $solrWebroot);
    }

  /**
   * Rebuilds the search index by iterating over all items and folders and indexing each of them
   */
  public function rebuildIndex($progressDao = null)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $itemModel = MidasLoader::loadModel('Item');
    $progressModel = MidasLoader::loadModel('Progress');
    if($progressDao)
      {
      $progressDao->setMaximum($folderModel->getTotalCount() + $itemModel->getTotalCount());
      $progressModel->save($progressDao);
      }

    $folderModel->iterateWithCallback('CALLBACK_CORE_FOLDER_SAVED', 'folder', array(
      'metadataChanged' => true,
      'progress' => $progressDao));

    $itemModel->iterateWithCallback('CALLBACK_CORE_ITEM_SAVED', 'item', array(
      'metadataChanged' => true,
      'progress' => $progressDao));

    if($progressDao)
      {
      $progressDao->setMessage('Optimizing index...');
      $progressModel->save($progressDao);
      }
    $index = $this->getSolrIndex();
    $index->optimize();
    }
  } // end class
