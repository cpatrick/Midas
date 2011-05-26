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

class Helloworld_Upgrade_1_0_1 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {
    
    }
    
  public function mysql()
    {
    $sql = "CREATE TABLE IF NOT EXISTS helloworld_helloupgrade1 (
                  id int(11) NOT NULL AUTO_INCREMENT,
                  PRIMARY KEY (id)
                )";
    $this->db->query($sql);
    }
    
  public function pgsql()
    {
    
    }
    
  public function postUpgrade()
    {
    
    }
}
?>
