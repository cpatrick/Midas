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

/** Job Dao */
class Remoteprocessing_JobDao extends Remoteprocessing_AppDao
  {
  public $_model = 'Job';
  public $_module = 'remoteprocessing';


  /** get Items */
  function getItems()
    {
    return $this->getModel()->getRelatedItems($this);
    }
  }