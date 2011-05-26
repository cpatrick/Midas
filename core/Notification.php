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

/** notification manager*/
class Notification extends MIDAS_Notification
  {
  public $_components = array('Utility');
  /** init notification process*/
  public function init($type, $params)
    {
    switch ($type)
      {
      case MIDAS_NOTIFY_GET_DASBOARD:
        return $this->_getDasboard();
        break;

      default:
        break;
      }
    }//end init  
    
  /** generate Dasboard information */
  private function _getDasboard()
    {
    $return = array();
    $return['Database'] = array(true); //If you are here it works...
    $return['Image Magick'] = array($this->Component->Utility->isImageMagickWorking());
    $return['Config Folder Writable'] = array(is_writable(BASE_PATH.'/core/configs'));
    $return['Data Folder Writable'] = array(is_writable(BASE_PATH.'/data'));
    $return['Temporary Folder Writable'] = array(is_writable(BASE_PATH.'/tmp'));
    
    return $return;
    }//end _getDasboard
  } //end class
?>