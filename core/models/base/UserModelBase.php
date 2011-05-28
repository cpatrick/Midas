<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** UserModelBase */
abstract class UserModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'user';
    $this->_key = 'user_id';
    $this->_mainData = array(
      'user_id' => array('type' => MIDAS_DATA),
      'firstname' => array('type' => MIDAS_DATA),
      'lastname' => array('type' => MIDAS_DATA),
      'email' => array('type' => MIDAS_DATA),
      'thumbnail' => array('type' => MIDAS_DATA),
      'company' => array('type' => MIDAS_DATA),
      'password' => array('type' => MIDAS_DATA),
      'creation' => array('type' => MIDAS_DATA),
      'folder_id' => array('type' => MIDAS_DATA),
      'admin' => array('type' => MIDAS_DATA),    
      'privacy' => array('type' => MIDAS_DATA),    
      'publicfolder_id' => array('type' => MIDAS_DATA),
      'privatefolder_id' => array('type' => MIDAS_DATA),
      'view' => array('type' => MIDAS_DATA),
      'uuid' => array('type' => MIDAS_DATA),
      'folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'folder_id', 'child_column' => 'folder_id'),
      'public_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'publicfolder_id', 'child_column' => 'folder_id'),
      'private_folder' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Folder', 'parent_column' => 'privatefolder_id', 'child_column' => 'folder_id'),
      'groups' =>  array('type' => MIDAS_MANY_TO_MANY, 'model' => 'Group', 'table' => 'user2group', 'parent_column' => 'user_id', 'child_column' => 'group_id'),
      'invitations' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'CommunityInvitation', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      'folderpolicyuser' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Folderpolicyuser', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      'feeds' => array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Feed', 'parent_column' => 'user_id', 'child_column' => 'user_id'),
      ); 
    $this->initialize(); // required
    } // end __construct()
  
  /** Abstract functions */
  abstract function getByEmail($email);
  abstract function getByUser_id($userid);
  abstract function getUserCommunities($userDao);
  abstract function getByUuid($uuid);
  /** Returns a user given its folder (either public,private or base folder) */
  abstract function getByFolder($folder);
  
  /** save */
  public function save($dao)
    {
    if(!isset($dao->uuid) || empty($dao->uuid))
      {
      $dao->setUuid(uniqid() . md5(mt_rand()));
      }
    parent::save($dao);
    } 
    
  /** delete*/
  public function delete($dao)
    {
    //TODO
    $modelLoad = new MIDAS_ModelLoader();     
    $ciModel = $modelLoad->loadModel('CommunityInvitation');
    $invitations = $dao->getInvitations();
    foreach($invitations as $invitation)
      {
      $ciModel->delete($invitation);
      }
      
    parent::delete($dao);
    }// delete
 
  /** plus one view*/
  function incrementViewCount($userDao)
    {
    if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $userDao->view++;
    $this->save($userDao);
    }//end incrementViewCount
    
  /** create user */
  public function createUser($email, $password, $firstname, $lastname, $admin = 0)
    {    
    if(!is_string($email) || empty($email) || !is_string($password) || empty($password) || !is_string($firstname)
        || empty($firstname) || !is_string($lastname) || empty($lastname) || !is_numeric($admin))
      {
      throw new Zend_Exception("Error Parameters.");
      }

    // Check ifthe user already exists based on the email address
    if($this->getByEmail($email) != null)
      {
      throw new Zend_Exception("User already exists.");
      }
      
    Zend_Loader::loadClass('UserDao', BASE_PATH.'/core/models/dao');
    $passwordPrefix = Zend_Registry::get('configGlobal')->password->prefix;
    if(isset($passwordPrefix) && !empty($passwordPrefix))
      {
      $password = $passwordPrefix.$password;
      }
    $userDao = new UserDao();
    $userDao->setFirstname(ucfirst($firstname));
    $userDao->setLastname(ucfirst($lastname));
    $userDao->setEmail(strtolower($email));
    $userDao->setCreation(date('c'));
    $userDao->setPassword(md5($password));
    $userDao->setAdmin($admin);
    
    // check gravatar
    $gravatarUrl = $this->getGravatarUrl($email);
    if($gravatarUrl != false)
      {
      $userDao->setThumbnail($gravatarUrl);
      }
    
    parent::save($userDao);

    $this->ModelLoader = new MIDAS_ModelLoader();
    $groupModel = $this->ModelLoader->loadModel('Group');
    $folderModel = $this->ModelLoader->loadModel('Folder');
    $folderpolicygroupModel = $this->ModelLoader->loadModel('Folderpolicygroup');
    $folderpolicyuserModel = $this->ModelLoader->loadModel('Folderpolicyuser');
    $feedModel = $this->ModelLoader->loadModel('Feed');
    $feedpolicygroupModel = $this->ModelLoader->loadModel('Feedpolicygroup');
    $feedpolicyuserModel = $this->ModelLoader->loadModel('Feedpolicyuser');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
        
    $folderGlobal = $folderModel->createFolder('user_' . $userDao->getKey(), '', MIDAS_FOLDER_USERPARENT);
    $folderPrivate = $folderModel->createFolder('Private', '', $folderGlobal);
    $folderPublic = $folderModel->createFolder('Public', '', $folderGlobal);

    $folderpolicygroupModel->createPolicy($anonymousGroup, $folderPublic, MIDAS_POLICY_READ);
        
    $folderpolicyuserModel->createPolicy($userDao, $folderPrivate, MIDAS_POLICY_ADMIN);
    $folderpolicyuserModel->createPolicy($userDao, $folderGlobal, MIDAS_POLICY_ADMIN);
    $folderpolicyuserModel->createPolicy($userDao, $folderPublic, MIDAS_POLICY_ADMIN);

    $userDao->setFolderId($folderGlobal->getKey());
    $userDao->setPublicfolderId($folderPublic->getKey());
    $userDao->setPrivatefolderId($folderPrivate->getKey());

    parent::save($userDao);
    $this->getLogger()->info(__METHOD__ . " Registration: " . $userDao->getFullName() . " " . $userDao->getKey());

    $feed = $feedModel->createFeed($userDao, MIDAS_FEED_CREATE_USER, $userDao);
    $feedpolicygroupModel->createPolicy($anonymousGroup, $feed, MIDAS_POLICY_READ);
    $feedpolicyuserModel->createPolicy($userDao, $feed, MIDAS_POLICY_ADMIN);    

    return $userDao;
    }
  
    
  /**
   * Get either a Gravatar URL or complete image tag for a specified email address.
   *
   * @param string $email The email address
   * @param string $s Size in pixels, defaults to 80px [ 1 - 512 ]
   * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
   * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
   * @param boole $img True to return a complete IMG tag False for just the URL
   * @param array $atts Optional, additional key/value attributes to include in the IMG tag
   * @return String containing either just a URL or a complete image tag
   * @source http://gravatar.com/site/implement/images/php/
   */
  public function getGravatarUrl($email, $s = 32, $d = '404', $r = 'g', $img = false, $atts = array() )
    {
    $url = 'http://www.gravatar.com/avatar/';
    $url .= md5(strtolower(trim($email)));
    $url .= "?s=".$s."&d=".$d."&r=".$r;
    if($img) 
      {
      $url = '<img src="' . $url . '"';
      foreach($atts as $key => $val)
        {
        $url .= ' ' . $key . '="' . $val . '"';
        }
      $url .= ' />';
      }
    
    $header = get_headers($url, 1);
    if(strpos($header[0], '404 Not Found') != false)
      {
      return false;
      }
    return $url;
    }
} // end class UserModelBase