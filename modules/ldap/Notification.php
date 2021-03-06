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

/** notification manager */
class Ldap_Notification extends MIDAS_Notification
  {
  public $_models = array('User');
  public $_moduleModels = array('User');
  public $moduleName = 'ldap';

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    $this->addCallBack('CALLBACK_CORE_AUTHENTICATION', 'ldapLogin');
    $this->addCallBack('CALLBACK_CORE_CHECK_USER_EXISTS', 'userExists');
    $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
    $this->addCallBack('CALLBACK_CORE_RESET_PASSWORD', 'handleResetPassword');
    $this->addCallBack('CALLBACK_CORE_ALLOW_PASSWORD_CHANGE', 'allowPasswordChange');
    $this->addCallBack('CALLBACK_CORE_USER_PROFILE_FIELDS', 'getLdapLoginField');
    $this->addCallBack('CALLBACK_CORE_USER_SETTINGS_CHANGED', 'userSettingsChanged');
    }

  /**
   * Add an LDAP login field to the user profile form
   */
  public function getLdapLoginField($params)
    {
    if(!$this->userSession->Dao || !$this->userSession->Dao->isAdmin())
      {
      return null;
      }
    $user = $params['user'];

    $field = array('label' => 'LDAP Login',
                   'name' => 'ldapLogin',
                   'type' => 'text',
                   'position' => 'top',
                   'value' => '');
    $ldapUser = $this->Ldap_User->getByUser($user);
    if($ldapUser)
      {
      $field['value'] = $ldapUser->getLogin();
      }
    return $field;
    }

  /**
   * Handle the LDAP login field from the user settings form.  If it is set to the empty string,
   * deletes any existing ldap_user for the user. Otherwise will update or create an ldap_user record
   * with the new value. The user will then use that on subsequent logins.
   * @param fields The HTTP fields from the settings form
   * @param user The user dao being changed
   */
  public function userSettingsChanged($params)
    {
    $user = $params['user'];
    $fields = $params['fields'];

    if(!array_key_exists('ldapLogin', $fields))
      {
      throw new Zend_Exception('LDAP Login parameter was not passed');
      }
    $ldapLogin = $fields['ldapLogin'];

    $ldapUser = $this->Ldap_User->getByUser($user);
    if($ldapUser)
      {
      if($ldapLogin == '')
        {
        $this->Ldap_User->delete($ldapUser);
        }
      else
        {
        $ldapUser->setLogin($ldapLogin);
        }
      }
    else if($ldapLogin != '')
      {
      $ldapUserDao = MidasLoader::newDao('UserDao', 'ldap');
      $ldapUserDao->setUserId($user->getKey());
      $ldapUserDao->setLogin($ldapLogin);
      $this->Ldap_User->save($ldapUserDao);

      $user->setSalt('x'); // set an invalid salt so normal authentication won't work
      $this->User->save($user);
      }
    }

  /** generate admin Dashboard information */
  public function getDashboard()
    {
    $config = Zend_Registry::get('configsModules');
    $hostname = $config['ldap']->ldap->hostname;
    $port = (int)$config['ldap']->ldap->port;
    $proxybasedn = $config['ldap']->ldap->proxyBasedn;
    $protocolVersion = $config['ldap']->ldap->protocolVersion;
    $backupServer = $config['ldap']->ldap->backup;
    $bindn = $config['ldap']->ldap->bindn;
    $bindpw = $config['ldap']->ldap->bindpw;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;

    $ldap = ldap_connect($hostname, $port);
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);

    $server = false;
    $backup = false;

    if(isset($ldap) && $ldap !== false)
      {
      if($proxybasedn != '')
        {
        ldap_bind($ldap, $proxybasedn, $proxyPassword);
        }

      $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
      if($ldapbind != false)
        {
        $server = true;
        }

      if(!empty($backupServer))
        {
        $ldap = ldap_connect($backupServer);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);
        $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
        if($ldapbind != false)
          {
          $backup = true;
          }
        }
      }

    $return = array();
    $return['LDAP Server'] = array($server);
    if(!empty($backup))
      {
      $return['LDAP Backup Server'] = array($backup);
      }

    return $return;
    }

  /**
   * Look up whether the user exists in the ldap_user table
   * @return true or false
   */
  public function userExists($params)
    {
    $someone = $this->Ldap_User->getLdapUser($params['entry']);
    if($someone)
      {
      return true;
      }
    return false;
    }

  /** login using ldap instead of the normal mechanism */
  public function ldapLogin($params)
    {
    if(!isset($params['email']) || !isset($params['password']))
      {
      throw new Zend_Exception('Required parameter "email" or "password" missing');
      }

    $email = $params['email'];
    $password = $params['password'];

    $config = Zend_Registry::get('configsModules');
    $baseDn = $config['ldap']->ldap->basedn;
    $hostname = $config['ldap']->ldap->hostname;
    $port = (int)$config['ldap']->ldap->port;
    $protocolVersion = $config['ldap']->ldap->protocolVersion;
    $autoAddUnknownUser = $config['ldap']->ldap->autoAddUnknownUser;
    $searchTerm =  $config['ldap']->ldap->search;
    $useActiveDirectory = $config['ldap']->ldap->useActiveDirectory;
    $proxybasedn = $config['ldap']->ldap->proxyBasedn;
    $backup = $config['ldap']->ldap->backup;
    $bindn = $config['ldap']->ldap->bindn;
    $bindpw = $config['ldap']->ldap->bindpw;
    $proxyPassword = $config['ldap']->ldap->proxyPassword;

    if($searchTerm == 'uid')
      {
      $atCharPos = strpos($email, '@');
      if($atCharPos === false)
        {
        $ldapsearch = 'uid='.$email;
        }
      else
        {
        $ldapsearch = 'uid='.substr($email, 0, $atCharPos);
        }
      }
    else
      {
      $ldapsearch = $searchTerm.'='.$email;
      }

    $ldap = ldap_connect($hostname, $port);

    if($ldap !== false)
      {
      ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);
      if($useActiveDirectory)
        {
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        }
      if($proxybasedn != '')
        {
        $proxybind = ldap_bind($ldap, $proxybasedn, $proxyPassword);
        if(!$proxybind)
          {
          throw new Zend_Exception('Cannot bind proxy');
          }
        }

      $ldapbind = ldap_bind($ldap, $bindn, $bindpw);
      if(!$ldapbind && $backup)
        {
        $ldap = ldap_connect($backup);
        ldap_bind($ldap, $bindn, $bindpw);
        }

      // do an ldap search for the specified user
      $result = ldap_search($ldap, $baseDn, $ldapsearch, array('uid', 'cn', 'mail'));
      $someone = false;
      if($result != 0)
        {
        $entries = ldap_get_entries($ldap, $result);

        if($entries['count'] != 0)
          {
          $principal = $entries[0]['dn'];
          }
        if(isset($principal))
          {
          // Bind as this user
          set_error_handler('Ldap_Notification::eatWarnings'); //must not print and log warnings
          if(@ldap_bind($ldap, $principal, $password))
            {
            // Try to find the user in the MIDAS database
            $someone = $this->Ldap_User->getLdapUser($email);
            if($someone)
              {
              // convert to core user dao
              $someone = $someone->getUser();
              }
            else if($autoAddUnknownUser)
              {
              // If the user doesn't exist we add it
              $givenname = $entries[0]['cn'][0];
              if(!isset($givenname))
                {
                throw new Zend_Exception('No common name (cn) set in LDAP, cannot register user into Midas');
                }

              if($searchTerm == 'mail')
                {
                $ldapEmail = $email;
                }
              else
                {
                @$ldapEmail = $entries[0]['mail'][0]; //use ldap email listing for their actual email
                if(!isset($ldapEmail))
                  {
                  $ldapEmail = $email;
                  }
                }

              $names = explode(' ', $givenname);
              $firstname = ' ';
              $namesCount = count($names);
              if($namesCount > 1)
                {
                $firstname = $names[0];
                $lastname = $names[1];
                for($i = 2; $i < $namesCount; $i++)
                  {
                  $lastname .= ' '.$names[$i];
                  }
                }
              else
                {
                $lastname = $names[0];
                }
              $someone = $this->Ldap_User->createLdapUser($ldapEmail, $email, $password, $firstname, $lastname);
              $someone = $someone->getUser(); // convert to core user dao
              }
            }
          restore_error_handler();
          }
        ldap_free_result($result);
        }
      else
        {
        throw new Zend_Exception('Error occured searching the LDAP: '.ldap_error($ldap));
        }
      ldap_close($ldap);
      return $someone;
      }
    else
      {
      throw new Zend_Exception('Could not connect to LDAP at '.$hostname);
      }
    }//end ldaplogin

  /**
   * If a user is deleted, we must delete any corresponding ldap_user entries
   */
  public function handleUserDeleted($params)
    {
    $this->Ldap_User->deleteByUser($params['userDao']);
    }

  /**
   * If a user requests a password reset and they are an ldap user, we have to
   * send them an alternate email telling them how they should actually reset
   * their password.
   */
  public function handleResetPassword($params)
    {
    $ldapUser = $this->Ldap_User->getByUser($params['user']);
    if($ldapUser !== false)
      {
      $fc = Zend_Controller_Front::getInstance();
      $url = 'http://'.$_SERVER['SERVER_NAME'].'/'.$fc->getBaseUrl();
      $config = Zend_Registry::get('configsModules');
      $ldapServer = $config['ldap']->ldap->hostname;

      $text = "Hello,<br><br>You have asked for a new password for Midas.<br><br>";
      $text .= "We could not fulfill this request because your user account is managed by an external LDAP server.<br><br>";
      $text .= "Please contact the administrator of the LDAP server at <b>".$ldapServer."</b> to have your password changed.<br><br>";
      $text .= "<a href=\"".$url."\">".$url."</a><br>";
      $text .= "<br><br>Generated by Midas (<a href='".$url."'>".$url."</a>)";

      mail($params['user']->getEmail(),
        'Midas: Password request',
        $text,
        "From: \nReply-To: no-reply\nX-Mailer: PHP/".phpversion()."\nMIME-Version: 1.0\nContent-type: text/html; charset = UTF-8");

      return array(
        'status' => true,
        'message' => 'An email has been sent to the specified address');
      }
    return array('status' => false);
    }

  /**
   * We must disable password changes for ldap users
   */
  public function allowPasswordChange($params)
    {
    $user = $params['user'];
    if($this->Ldap_User->getByUser($user) !== false)
      {
      return array('allow' => false);
      }
    return array('allow' => true);
    }

  /**
   * This is used to suppress warnings from being written to the output and the
   * error log.  When searching, we don't want warnings to appear for invalid searches.
   */
  static function eatWarnings($errno, $errstr, $errfile, $errline)
    {
    return true;
    }
  } // end class
