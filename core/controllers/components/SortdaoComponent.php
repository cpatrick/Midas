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

/** Sort Daos*/
class SortdaoComponent extends AppComponent
  {
  public $field = '';
  public $order = 'asc';

  /** sort daos*/
  public function sortByDate($a, $b)
    {
    $field = $this->field;
    if($this->field == '' || !isset($a->$field))
      {
      throw new Zend_Exception("Error in field required by sortByDate.");
      }

    $a_t = strtotime($a->$field);
    $b_t = strtotime($b->$field);

    if($a_t == $b_t)
      {
      return 0;
      }

    if($this->order == 'asc')
      {
      return ($a_t > $b_t ) ? -1 : 1;
      }
    else
      {
      return ($a_t > $b_t ) ? 1 : -1;
      }
    }//end sortByDate

  /** sort by name*/
  public function sortByName($a, $b)
    {
    $field = $this->field;
    if($this->field == '' || !isset($a->$field))
      {
      throw new Zend_Exception("Error in field required sortByName.");
      }
    $a_n = strtolower($a->$field);
    $b_n = strtolower($b->$field);

    if($a_n == $b_n)
      {
      return 0;
      }

    if($this->order == 'asc')
      {
      return ($a_n < $b_n) ? -1 : 1;
      }
    else
      {
      return ($a_n < $b_n ) ? 1 : -1;
      }
    }//end sortByDate

  /** sort the results by number then by name*/
  public function sortByNumberThenName($a, $b)
    {
    $field = $this->field;
    $field2 = $this->field2;
    if($this->field == '' || !isset($a->$field) || $this->field2 == '' || !isset($a->$field2))
      {
      throw new Zend_Exception("Error in field required by sortByNumberThenName.");
      }
    $a_n = strtolower($a->$field);
    $b_n = strtolower($b->$field);
    $a2_n = strtolower($a->$field2);
    $b2_n = strtolower($b->$field2);

    if($a_n == $b_n && $a2_n == $b2_n)
      {
      return 0;
      }

    if($this->order == 'asc')
      {
      if($a_n == $b_n)
        {
        if($this->order2 == 'asc')
          {
          return ($a2_n < $b2_n) ? -1 : 1;
          }
        return ($a2_n < $b2_n) ? 1 : -1;
        }
      return ($a_n < $b_n) ? -1 : 1;
      }
    else
      {
      if($a_n == $b_n)
        {
        if($this->order2 == 'asc')
          {
          return ($a2_n < $b2_n) ? -1 : 1;
          }
        return ($a2_n < $b2_n) ? 1 : -1;
        }
      return ($a_n < $b_n ) ? 1 : -1;
      }
    }

  /** sort by number*/
  public function sortByNumber($a, $b)
    {
    $field = $this->field;
    if($this->field == '' || !isset($a->$field))
      {
      throw new Zend_Exception("Error in field required by sortByNumber.");
      }
    $a_n = strtolower($a->$field);
    $b_n = strtolower($b->$field);

    if($a_n == $b_n)
      {
      return 0;
      }

    if($this->order == 'asc')
      {
      return ($a_n < $b_n) ? -1 : 1;
      }
    else
      {
      return ($a_n < $b_n ) ? 1 : -1;
      }
    }//end sortByNumber

  /** Unique*/
  public function arrayUniqueDao($array, $keep_key_assoc = false)
    {
    $duplicate_keys = array();
    $tmp         = array();

    foreach($array as $key => $val)
      {
      // convert objects to arrays, in_array() does not support objects
      if(is_object($val))
        {
        $val = (array)$val;
        }

      if(!in_array($val, $tmp))
        {
        $tmp[] = $val;
        }
      else
        {
        $duplicate_keys[] = $key;
        }
      }

    foreach($duplicate_keys as $key)
      {
      unset($array[$key]);
      }

    return $keep_key_assoc ? $array : array_values($array);
    }
  } // end class
