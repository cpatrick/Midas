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

/** test hello model*/
class DashboardModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $db = Zend_Registry::get('dbAdapter');
    $configDatabase = Zend_Registry::get('configDatabase');
    if($configDatabase->database->adapter == 'PDO_PGSQL')
      {
      $db->query("SELECT setval('validation_dashboard_dashboard_id_seq', (SELECT MAX(dashboard_id) FROM validation_dashboard)+1);");
      $db->query("SELECT setval('validation_dashboard2folder_dashboard2folder_id_seq', (SELECT MAX(dashboard2folder_id) FROM validation_dashboard2folder)+1);");
      $db->query("SELECT setval('validation_dashboard2scalarresult_dashboard2scalarresult_id_seq', (SELECT MAX(dashboard2scalarresult_id) FROM  validation_dashboard2scalarresult)+1);");
      $db->query("SELECT setval('validation_scalarresult_scalarresult_id_seq', (SELECT MAX(scalarresult_id) FROM validation_scalarresult)+1);");
      }
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'validation'); // module dataset
    $this->enabledModules = array('validation');
    $this->_models = array('Folder', 'Item');
    $this->_daos = array('Folder', 'Item');
    Zend_Registry::set('modulesEnable', array());
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    parent::setUp();
    }

  /** testGetAll*/
  public function testGetAll()
    {
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $this->assertEquals(1, count($daos));
    }

  /**
   * test the fetching of results
   */
  public function testGetResults()
    {
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $this->assertEquals(2, count($dao->getResults()));
    }

  /**
   * test the fetching of Testing, Training, and Truth
   */
  public function testGetTestingTrainingAndTruth()
    {
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $testing = $dao->getTesting();
    $training = $dao->getTraining();
    $truth = $dao->getTruth();
    $this->assertNotEquals(false, $testing);
    $this->assertNotEquals(false, $training);
    $this->assertNotEquals(false, $truth);
    }

  /**
   * test consistency verification (same number of items in each folder, good
   * naming, etc.
   */
  public function testVerifyConsistency()
    {
    // Create training, testing, and truth folders
    $testingFolder = new FolderDao();
    $testingFolder->setName('testing');
    $testingFolder->setDescription('testing');
    $testingFolder->setParentId(-1);
    $this->Folder->save($testingFolder);
    $trainingFolder = new FolderDao();
    $trainingFolder->setName('training');
    $trainingFolder->setDescription('training');
    $trainingFolder->setParentId(-1);
    $this->Folder->save($trainingFolder);
    $truthFolder = new FolderDao();
    $truthFolder->setName('truth');
    $truthFolder->setDescription('truth');
    $truthFolder->setParentId(-1);
    $this->Folder->save($truthFolder);

    // Add items to the folders
    $trainingItem = null;
    $testingItem = null;
    $truthItem = null;
    for($i = 0; $i < 3; ++$i)
      {
      $trainingItem = new ItemDao();
      $testingItem = new ItemDao();
      $truthItem = new ItemDao();
      $trainingItem->setName('img0'.$i.'.mha');
      $testingItem->setName('img0'.$i.'.mha');
      $truthItem->setName('img0'.$i.'.mha');
      $trainingItem->setDescription('training img '.$i);
      $testingItem->setDescription('testing img '.$i);
      $truthItem->setDescription('truth img '.$i);
      $trainingItem->setType(0);
      $testingItem->setType(0);
      $truthItem->setType(0);
      $this->Item->save($trainingItem);
      $this->Item->save($testingItem);
      $this->Item->save($truthItem);
      $this->Folder->addItem($trainingFolder, $trainingItem);
      $this->Folder->addItem($testingFolder, $testingItem);
      $this->Folder->addItem($truthFolder, $truthItem);
      }

    // Acquire the dashboard from the database
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];

    // Add testing, training, and truth to the dashboard
    $dashboardModel->setTraining($dao, $trainingFolder);
    $dashboardModel->setTesting($dao, $testingFolder);
    $dashboardModel->setTruth($dao, $truthFolder);

    // Reload the dashboard and check it for consistency
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $this->assertEquals(true, $dashboardModel->checkConsistency($dao));

    // Remove a testing dataset and check consistency
    $this->Folder->removeItem($testingFolder, $testingItem);
    $this->assertEquals(false, $dashboardModel->checkConsistency($dao));

    // Re-add the item and check again :)
    // Re save the item first, as removing it from the folder deleted it
    $this->Item->save($testingItem);
    $this->Folder->addItem($testingFolder, $testingItem);
    $this->assertEquals(true, $dashboardModel->checkConsistency($dao));

    // Modifying a item name and checking for consistency
    $testingItem->setName('BADNAME');
    $this->Item->save($testingItem);
    $this->assertEquals(false, $dashboardModel->checkConsistency($dao));
    }

  /**
   * test addResult function
   */
  public function testAddRemoveResult()
    {
    // Create training, testing, and truth folders
    $testingFolder = new FolderDao();
    $testingFolder->setName('testing');
    $testingFolder->setDescription('testing');
    $testingFolder->setParentId(-1);
    $this->Folder->save($testingFolder);
    $trainingFolder = new FolderDao();
    $trainingFolder->setName('training');
    $trainingFolder->setDescription('training');
    $trainingFolder->setParentId(-1);
    $this->Folder->save($trainingFolder);
    $truthFolder = new FolderDao();
    $truthFolder->setName('truth');
    $truthFolder->setDescription('truth');
    $truthFolder->setParentId(-1);
    $this->Folder->save($truthFolder);

    // Create result folder
    $resultFolder = new FolderDao();
    $resultFolder->setName('result');
    $resultFolder->setDescription('result');
    $resultFolder->setParentId(-1);
    $this->Folder->save($resultFolder);

    // Add items to the folders
    $trainingItem = null;
    $testingItem = null;
    $truthItem = null;
    $resultItem = null;
    for($i = 0; $i < 3; ++$i)
      {
      $trainingItem = new ItemDao();
      $testingItem = new ItemDao();
      $truthItem = new ItemDao();
      $resultItem = new ItemDao();

      $trainingItem->setName('img0'.$i.'.mha');
      $testingItem->setName('img0'.$i.'.mha');
      $truthItem->setName('img0'.$i.'.mha');
      $resultItem->setName('img0'.$i.'.mha');

      $trainingItem->setDescription('training img '.$i);
      $testingItem->setDescription('testing img '.$i);
      $truthItem->setDescription('truth img '.$i);
      $resultItem->setDescription('result img '.$i);

      $trainingItem->setType(0);
      $testingItem->setType(0);
      $truthItem->setType(0);
      $resultItem->setType(0);

      $this->Item->save($trainingItem);
      $this->Item->save($testingItem);
      $this->Item->save($truthItem);
      $this->Item->save($resultItem);

      $this->Folder->addItem($trainingFolder, $trainingItem);
      $this->Folder->addItem($testingFolder, $testingItem);
      $this->Folder->addItem($truthFolder, $truthItem);
      $this->Folder->addItem($resultFolder, $resultItem);

      }

    // Acquire the dashboard from the database
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];

    // Add testing, training, and truth to the dashboard
    $dashboardModel->setTraining($dao, $trainingFolder);
    $dashboardModel->setTesting($dao, $testingFolder);
    $dashboardModel->setTruth($dao, $truthFolder);

    // Reload the dashboard and check it for consistency
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $dashboardModel->addResult($dao, $resultFolder);
    $this->assertEquals(3, count($dao->getResults()));

    // Remove a couple of result sets
    $results = $dao->getResults();
    $dashboardModel->removeResult($dao, $results[0]);
    $dashboardModel->removeResult($dao, $results[1]);
    $this->assertEquals(1, count($dao->getResults()));
    }

  /**
   * test addResult function
   */
  public function testSetScores()
    {
    // Create training, testing, and truth folders
    $testingFolder = new FolderDao();
    $testingFolder->setName('testing');
    $testingFolder->setDescription('testing');
    $testingFolder->setParentId(-1);
    $this->Folder->save($testingFolder);
    $trainingFolder = new FolderDao();
    $trainingFolder->setName('training');
    $trainingFolder->setDescription('training');
    $trainingFolder->setParentId(-1);
    $this->Folder->save($trainingFolder);
    $truthFolder = new FolderDao();
    $truthFolder->setName('truth');
    $truthFolder->setDescription('truth');
    $truthFolder->setParentId(-1);
    $this->Folder->save($truthFolder);

    // Create result folder
    $resultFolder = new FolderDao();
    $resultFolder->setName('result');
    $resultFolder->setDescription('result');
    $resultFolder->setParentId(-1);
    $this->Folder->save($resultFolder);

    // Add items to the folders
    $trainingItem = null;
    $testingItem = null;
    $truthItem = null;
    $resultItem = null;
    for($i = 0; $i < 3; ++$i)
      {
      $trainingItem = new ItemDao();
      $testingItem = new ItemDao();
      $truthItem = new ItemDao();
      $resultItem = new ItemDao();

      $trainingItem->setName('img0'.$i.'.mha');
      $testingItem->setName('img0'.$i.'.mha');
      $truthItem->setName('img0'.$i.'.mha');
      $resultItem->setName('img0'.$i.'.mha');

      $trainingItem->setDescription('training img '.$i);
      $testingItem->setDescription('testing img '.$i);
      $truthItem->setDescription('truth img '.$i);
      $resultItem->setDescription('result img '.$i);

      $trainingItem->setType(0);
      $testingItem->setType(0);
      $truthItem->setType(0);
      $resultItem->setType(0);

      $this->Item->save($trainingItem);
      $this->Item->save($testingItem);
      $this->Item->save($truthItem);
      $this->Item->save($resultItem);

      $this->Folder->addItem($trainingFolder, $trainingItem);
      $this->Folder->addItem($testingFolder, $testingItem);
      $this->Folder->addItem($truthFolder, $truthItem);
      $this->Folder->addItem($resultFolder, $resultItem);

      }

    // Acquire the dashboard from the database
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];

    // Add testing, training, and truth to the dashboard
    $dashboardModel->setTraining($dao, $trainingFolder);
    $dashboardModel->setTesting($dao, $testingFolder);
    $dashboardModel->setTruth($dao, $truthFolder);

    // Reload the dashboard and check it for consistency
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $dashboardModel->addResult($dao, $resultFolder);

    // Get the results
    $results = $dao->getResults();
    $firstResult = $results[2];
    $items = $firstResult->getItems();
    $numItems = count($items);
    $values = array();
    for($i = 0; $i < $numItems; ++$i)
      {
      $values[$items[$i]->getKey()] = $i * 15;
      }
    $dashboardModel->setScores($dao, $firstResult, $values);
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $this->assertEquals(3, count($dao->getScores()));
    $scores = $dashboardModel->getScores($dao, $resultFolder);
    $this->assertEquals($values, $scores);
    }

  /**
   * test addResult function
   */
  public function testGetAllScores()
    {
    // Create training, testing, and truth folders
    $testingFolder = new FolderDao();
    $testingFolder->setName('testing');
    $testingFolder->setDescription('testing');
    $testingFolder->setParentId(-1);
    $this->Folder->save($testingFolder);
    $trainingFolder = new FolderDao();
    $trainingFolder->setName('training');
    $trainingFolder->setDescription('training');
    $trainingFolder->setParentId(-1);
    $this->Folder->save($trainingFolder);
    $truthFolder = new FolderDao();
    $truthFolder->setName('truth');
    $truthFolder->setDescription('truth');
    $truthFolder->setParentId(-1);
    $this->Folder->save($truthFolder);

    // Create result folder
    $resultFolder = new FolderDao();
    $resultFolder->setName('result');
    $resultFolder->setDescription('result');
    $resultFolder->setParentId(-1);
    $this->Folder->save($resultFolder);

    // Add items to the folders
    $trainingItem = null;
    $testingItem = null;
    $truthItem = null;
    $resultItem = null;
    for($i = 0; $i < 3; ++$i)
      {
      $trainingItem = new ItemDao();
      $testingItem = new ItemDao();
      $truthItem = new ItemDao();
      $resultItem = new ItemDao();

      $trainingItem->setName('img0'.$i.'.mha');
      $testingItem->setName('img0'.$i.'.mha');
      $truthItem->setName('img0'.$i.'.mha');
      $resultItem->setName('img0'.$i.'.mha');

      $trainingItem->setDescription('training img '.$i);
      $testingItem->setDescription('testing img '.$i);
      $truthItem->setDescription('truth img '.$i);
      $resultItem->setDescription('result img '.$i);

      $trainingItem->setType(0);
      $testingItem->setType(0);
      $truthItem->setType(0);
      $resultItem->setType(0);

      $this->Item->save($trainingItem);
      $this->Item->save($testingItem);
      $this->Item->save($truthItem);
      $this->Item->save($resultItem);

      $this->Folder->addItem($trainingFolder, $trainingItem);
      $this->Folder->addItem($testingFolder, $testingItem);
      $this->Folder->addItem($truthFolder, $truthItem);
      $this->Folder->addItem($resultFolder, $resultItem);

      }

    // Acquire the dashboard from the database
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];

    // Add testing, training, and truth to the dashboard
    $dashboardModel->setTraining($dao, $trainingFolder);
    $dashboardModel->setTesting($dao, $testingFolder);
    $dashboardModel->setTruth($dao, $truthFolder);

    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $dashboardModel->addResult($dao, $resultFolder);
    $dashboardModel->addResult($dao, $truthFolder);
    $dashboardModel->addResult($dao, $testingFolder);
    $results = $dao->getResults();
    $dashboardModel->removeResult($dao, $results[0]);
    $dashboardModel->removeResult($dao, $results[1]);

    // Get the results
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $results = $dao->getResults();
    $values = array();
    foreach($results as $result)
      {
      $folderId = $result->getKey();
      $values[$folderId] = array();
      $items = $result->getItems();
      $count = 1;
      foreach($items as $item)
        {
        $values[$folderId][$item->getKey()] = $count * 15;
        ++$count;
        }
      $dashboardModel->setScores($dao, $result, $values[$folderId]);
      }

    // Check getAllScores
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $allScores = $dashboardModel->getAllScores($dao);
    foreach($allScores as $fid => $scores)
      {
      $this->assertEquals($values[$fid], $scores);
      }

    // Check that removal works and that getAllScores reflects that
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $results = $dao->getResults();
    $dashboardModel->removeResult($dao, $results[0]);
    unset($values[$results[0]->getKey()]);
    $allScores = $dashboardModel->getAllScores($dao);
    $this->assertEquals(count($values), count($allScores));
    foreach($allScores as $fid => $scores)
      {
      $this->assertEquals($values[$fid], $scores);
      }
    }

  /**
   * test addResult function
   */
  public function testSetScore()
    {
    // Create training, testing, and truth folders
    $testingFolder = new FolderDao();
    $testingFolder->setName('testing');
    $testingFolder->setDescription('testing');
    $testingFolder->setParentId(-1);
    $this->Folder->save($testingFolder);
    $trainingFolder = new FolderDao();
    $trainingFolder->setName('training');
    $trainingFolder->setDescription('training');
    $trainingFolder->setParentId(-1);
    $this->Folder->save($trainingFolder);
    $truthFolder = new FolderDao();
    $truthFolder->setName('truth');
    $truthFolder->setDescription('truth');
    $truthFolder->setParentId(-1);
    $this->Folder->save($truthFolder);

    // Create result folder
    $resultFolder = new FolderDao();
    $resultFolder->setName('result');
    $resultFolder->setDescription('result');
    $resultFolder->setParentId(-1);
    $this->Folder->save($resultFolder);

    // Add items to the folders
    $trainingItem = null;
    $testingItem = null;
    $truthItem = null;
    $resultItem = null;
    for($i = 0; $i < 3; ++$i)
      {
      $trainingItem = new ItemDao();
      $testingItem = new ItemDao();
      $truthItem = new ItemDao();
      $resultItem = new ItemDao();

      $trainingItem->setName('img0'.$i.'.mha');
      $testingItem->setName('img0'.$i.'.mha');
      $truthItem->setName('img0'.$i.'.mha');
      $resultItem->setName('img0'.$i.'.mha');

      $trainingItem->setDescription('training img '.$i);
      $testingItem->setDescription('testing img '.$i);
      $truthItem->setDescription('truth img '.$i);
      $resultItem->setDescription('result img '.$i);

      $trainingItem->setType(0);
      $testingItem->setType(0);
      $truthItem->setType(0);
      $resultItem->setType(0);

      $this->Item->save($trainingItem);
      $this->Item->save($testingItem);
      $this->Item->save($truthItem);
      $this->Item->save($resultItem);

      $this->Folder->addItem($trainingFolder, $trainingItem);
      $this->Folder->addItem($testingFolder, $testingItem);
      $this->Folder->addItem($truthFolder, $truthItem);
      $this->Folder->addItem($resultFolder, $resultItem);

      }

    // Acquire the dashboard from the database
    $dashboardModel = MidasLoader::loadModel('Dashboard', 'validation');
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];

    // Add testing, training, and truth to the dashboard
    $dashboardModel->setTraining($dao, $trainingFolder);
    $dashboardModel->setTesting($dao, $testingFolder);
    $dashboardModel->setTruth($dao, $truthFolder);

    // Reload the dashboard and check it for consistency
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $dashboardModel->addResult($dao, $resultFolder);

    // Get the results
    $results = $dao->getResults();
    $firstResult = $results[2];
    $items = $firstResult->getItems();
    $values = array();
    $count = 0;
    foreach($items as $item)
      {
      $values[$item->getKey()] = $count * 15;
      $dashboardModel->setScore($dao, $firstResult, $item, $count * 15);
      ++$count;
      }
    $daos = $dashboardModel->getAll();
    $dao = $daos[0];
    $this->assertEquals(3, count($dao->getScores()));
    $scores = $dashboardModel->getScores($dao, $resultFolder);
    $this->assertEquals($values, $scores);
    }
  }
