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

require_once BASE_PATH.'/core/models/base/FeedpolicygroupModelBase.php';

/**
 * \class Feedpolicygroup
 * \brief Cassandra Model
 */
class FeedpolicygroupModel extends FeedpolicygroupModelBase
{
  /** getPolicy
   * @return FeedpolicygroupDao
   */
  public function getPolicy($group, $feed)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
    
    $feedid = $feed->getKey();
    $groupid = $group->getKey();
    
    $column = 'feed_'.$feedid;    
    $feedarray = $this->database->getCassandra('groupfeedpolicy', $groupid, array($column));

    if(empty($feedarray))
      {
      return null;  
      }
      
    // Massage the data to the proper format
    $newarray['feed_id'] = $feedid;
    $newarray['group_id'] = $groupid;
    $newarray['policy'] = $feedarray[$column];
    
    return $this->initDao('Feedpolicygroup', $newarray);  
    } // end getPolicy

  /** Custom save command */
  public function save($dao)
    {
    $instance = $this->_name."Dao";
    if(!$dao instanceof $instance)
      {
      throw new Zend_Exception("Should be an object (".$instance.").");
      }
      
    try 
      {
      $feedid = $dao->getFeedId();
      $groupid = $dao->getGroupId();
      
      // Add the feed to the UserFeedPolicy
      $column = 'feed_'.$feedid;
      $dataarray = array();
      $dataarray[$column] = $dao->getPolicy();
      
      $column_family = new ColumnFamily($this->database->getDB(), 'groupfeedpolicy');
      $column_family->insert($groupid, $dataarray);  
      
      // Add the feed to the UserFeed (this is a super column)
      $column = 'feed_'.$feedid;
      $dataarray = array();
      $dataarray[$column] = array();
      $dataarray[$column]['group_'.$groupid] = $dao->getPolicy();
      
      $column_family = new ColumnFamily($this->database->getDB(), 'userfeed');
      $column_family->insert($groupid, $dataarray);
      
      // Add the policy to the CommunityFeed ifwe have a community
      if(isset($dao->community) && $dao->community)
        {
        $column = 'feed_'.$feedid;
        $dataarray = array();
        $dataarray[$column] = array();
        $dataarray[$column]['group_'.$groupid] = $dao->getPolicy();
      
        $column_family = new ColumnFamily($this->database->getDB(), 'communityfeed');
        $column_family->insert($dao->community->getCommunityId(), $dataarray);  
        }
      
      
      } 
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      } 
    
    $dao->saved = true;
    return true;
    } // end save()  
    
  /** Custome delete command */
  public function delete($dao)
    {
    // No DAO passed we just return  
    if($dao == null)
      {
      return false;  
      } 
        
    $instance = ucfirst($this->_name)."Dao";
    if(get_class($dao) !=  $instance)
      {
      throw new Zend_Exception("Should be an object (".$instance."). It was: ".get_class($dao) );
      }
    if(!$dao->saved)
      {
      throw new Zend_Exception("The dao should be saved first ...");
      }
    
    try 
      {
      // Remove the column user from the feed 
      $feedid = $dao->getFeedId();
      $groupid = $dao->getGroupId();
      $column = 'feed_'.$feedid;   
      $cf = new ColumnFamily($this->database->getDB(), 'groupfeedpolicy');
      $cf->remove($groupid, array($column)); 

      // Remove from the UserFeed also
      $column = 'feed_'.$feedid;  // super column
      $cf = new ColumnFamily($this->database->getDB(), 'userfeed');
      $cf->remove($groupid, array('group_'.$groupid), $column);      
      }    
    catch(Exception $e) 
      {
      throw new Zend_Exception($e); 
      }    
    $dao->saved = false;
    return true;
    }
    
}  // end class
?>
