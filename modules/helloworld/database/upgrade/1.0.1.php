<?php

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