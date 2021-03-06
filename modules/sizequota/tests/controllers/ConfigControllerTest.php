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

/** Test management of the size quotas behavior */
class PerformTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->setupDatabase(array('default'), 'sizequota');
    $this->enabledModules = array('api', 'sizequota');
    $this->_models = array('Assetstore', 'Community', 'Setting', 'User');

    parent::setUp();
    }

  /** Test the functionality of the main module config page */
  public function testAdminConfig()
    {
    // Make sure we don't have existing settings for defaults
    $this->assertTrue($this->Setting->getValueByName('defaultuserquota', 'sizequota') === null);
    $this->assertTrue($this->Setting->getValueByName('defaultcommunityquota', 'sizequota') === null);

    // Use the admin user so we can configure the module
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());

    // Make sure we can render the config controller
    $this->dispatchUrI('/sizequota/config/index', $userDao);
    $this->assertController('config');
    $this->assertAction('index');

    // Set some default settings for user and community quota
    $this->resetAll();
    $this->request->setMethod('POST');
    $this->params['defaultuserquota'] = '';
    $this->params['defaultcommunityquota'] = '10000';
    $this->params['communityQuotaUnit'] = '100';
    $this->params['userQuotaUnit'] = '1024';
    $this->params['submitConfig'] = 'true';
    $this->dispatchUrI('/sizequota/config/index', $userDao);
    $resp = JsonComponent::decode($this->getBody());
    $this->assertTrue($resp[0] == true);

    // Make sure our settings were set correctly
    $this->assertTrue($this->Setting->getValueByName('defaultuserquota', 'sizequota') === '');
    $this->assertTrue($this->Setting->getValueByName('defaultcommunityquota', 'sizequota') === '1000000');
    }

  /** Test management of user- and community-specific quotas */
  public function testFolderConfig()
    {
    // Set some defaults
    $this->Setting->setConfig('defaultuserquota', '', 'sizequota');
    $this->Setting->setConfig('defaultcommunityquota', '10000', 'sizequota');

    $usersFile = $this->loadData('User', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());

    // Exception if no folder id is set
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder', null, true);

    // Exception if invalid folder id is set
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder?folderId=-2', null, true);

    // Exception if invalid policy
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder?folderId='.$user1->getFolderId(), null, true);

    // Exception if non-root folder is passed
    $subfolders = $user1->getFolder()->getFolders();
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder?folderId='.$subfolders[0]->getKey(), $user1, true);

    // User 1 should be able to view their own root folder's quota info, but not see the form to change it
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder?folderId='.$user1->getFolderId(), $user1);
    $this->assertController('config');
    $this->assertAction('folder');
    $this->assertQueryContentRegex('span#hUsedSpaceValue', '/^0.0 KB$/');
    $this->assertQueryContentRegex('span#hQuotaValue', '/^Unlimited$/');
    $this->assertQueryContentRegex('span#hFreeSpaceValue', '/^$/');
    $this->assertQueryContentRegex('span#quotaValue', '/^$/');
    $this->assertQueryContentRegex('span#usedSpaceValue', '/^0$/');
    $this->assertNotQuery('form.quotaConfigForm');
    $this->assertNotQuery('input[type="submit"][name="submitQuota"]');

    // Admin user should be able to view user 1's quota info, and also see the form to change it
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder?folderId='.$user1->getFolderId(), $adminUser);
    $this->assertController('config');
    $this->assertAction('folder');
    $this->assertQueryContentRegex('span#hUsedSpaceValue', '/^0.0 KB$/');
    $this->assertQueryContentRegex('span#hQuotaValue', '/^Unlimited$/');
    $this->assertQueryContentRegex('span#hFreeSpaceValue', '/^$/');
    $this->assertQueryContentRegex('span#quotaValue', '/^$/');
    $this->assertQueryContentRegex('span#usedSpaceValue', '/^0$/');
    $this->assertQuery('form.quotaConfigForm');
    $this->assertQuery('input[type="submit"][name="submitQuota"]');

    $folderQuotaModel = MidasLoader::loadModel('FolderQuota', 'sizequota');

    // User 1 should not be able to change their own quota
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/foldersubmit?quota=1234&unit=1usedefault='.MIDAS_USE_SPECIFIC_QUOTA.
                       '&folderId='.$user1->getFolderId(), $user1, true);
    $this->assertEquals($folderQuotaModel->getUserQuota($user1), '');

    // Admin user should be able to change quota for a user
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/foldersubmit?quota=1234&unit=100&usedefault='.MIDAS_USE_SPECIFIC_QUOTA.
                       '&folderId='.$user1->getFolderId(), $adminUser);
    $this->assertEquals($folderQuotaModel->getUserQuota($user1), '123400');

    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/foldersubmit?quota=1234&unit=1usedefault='.MIDAS_USE_DEFAULT_QUOTA.
                       '&folderId='.$user1->getFolderId(), $adminUser);
    $this->assertEquals($folderQuotaModel->getUserQuota($user1), '');

    $commFile = $this->loadData('Community', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());

    // User 1 should not be able to see community quota (no privileges on the root folder)
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder?folderId='.$comm->getFolderId(), $user1, true);

    // Admin user should be able to see community quota
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/folder?folderId='.$comm->getFolderId(), $adminUser);
    $this->assertController('config');
    $this->assertAction('folder');
    $this->assertQueryContentRegex('span#hUsedSpaceValue', '/^0.0 KB$/');
    $this->assertQueryContentRegex('span#hQuotaValue', '/^9.8 KB$/');
    $this->assertQueryContentRegex('span#hFreeSpaceValue', '/^9.8 KB$/');
    $this->assertQueryContentRegex('span#quotaValue', '/^10000$/');
    $this->assertQueryContentRegex('span#usedSpaceValue', '/^$/');
    $this->assertQuery('form.quotaConfigForm');
    $this->assertQuery('input[type="submit"][name="submitQuota"]');

    // Admin should be able to set new community quota
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/foldersubmit?quota=&unit=1024&usedefault='.MIDAS_USE_SPECIFIC_QUOTA.
                       '&folderId='.$comm->getFolderId(), $adminUser);
    $this->assertEquals($folderQuotaModel->getCommunityQuota($comm), '');

    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/foldersubmit?quota=&unit=1024&usedefault='.MIDAS_USE_DEFAULT_QUOTA.
                       '&folderId='.$comm->getFolderId(), $adminUser);
    $this->assertEquals($folderQuotaModel->getCommunityQuota($comm), '10000');
    }

  /** Test the ajax getFreeSpace() call */
  public function testGetFreeSpace()
    {
    // Set some defaults
    $this->Setting->setConfig('defaultuserquota', '', 'sizequota');
    $this->Setting->setConfig('defaultcommunityquota', '10000', 'sizequota');

    $usersFile = $this->loadData('User', 'default');
    $user1 = $this->User->load($usersFile[0]->getKey());
    $adminUser = $this->User->load($usersFile[2]->getKey());

    $commFile = $this->loadData('Community', 'default');
    $comm = $this->Community->load($commFile[0]->getKey());

    // Exception if no folder id is set
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/getfreespace', $adminUser);
    $resp = JsonComponent::decode($this->getBody());
    $this->assertTrue($resp['status'] == false);
    $this->assertEquals($resp['message'], 'Missing folderId parameter');

    // Exception if invalid folder id is set
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/getfreespace?folderId=-7', $adminUser);
    $resp = JsonComponent::decode($this->getBody());
    $this->assertTrue($resp['status'] == false);
    $this->assertEquals($resp['message'], 'Invalid folder');

    // Exception if no read privileges
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/getfreespace?folderId='.$user1->getFolderId(), null);
    $resp = JsonComponent::decode($this->getBody());
    $this->assertTrue($resp['status'] == false);
    $this->assertEquals($resp['message'], 'Invalid policy');

    // User with read privileges should be able to get free space
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/getfreespace?folderId='.$user1->getFolderId(), $user1);
    $resp = JsonComponent::decode($this->getBody());
    $this->assertTrue($resp['status'] == true);

    // This should also work on non-root folders
    $this->resetAll();
    $this->dispatchUrI('/sizequota/config/getfreespace?folderId=1001', $user1);
    $resp = JsonComponent::decode($this->getBody());
    $this->assertTrue($resp['status'] == true);

    // Should also work for community folders
    $this->resetAll();
    $commFolders = $comm->getFolder()->getFolders();
    $this->dispatchUrI('/sizequota/config/getfreespace?folderId='.$commFolders[0]->getKey(), $adminUser);
    $resp = JsonComponent::decode($this->getBody());
    $this->assertTrue($resp['status'] == true);
    $this->assertEquals($resp['freeSpace'], '10000');
    $this->assertEquals($resp['hFreeSpace'], '9.8 KB');
    }
  }
