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

/**
 * Trend Model Base
 */
abstract class Tracker_TrendModelBase extends Tracker_AppModel
  {
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'tracker_trend';
    $this->_key = 'trend_id';
    $this->_mainData = array(
        'trend_id' => array('type' => MIDAS_DATA),
        'producer_id' => array('type' => MIDAS_DATA),
        'metric_name' => array('type' => MIDAS_DATA),
        'display_name' => array('type' => MIDAS_DATA),
        'unit' => array('type' => MIDAS_DATA),
        'config_item_id' => array('type' => MIDAS_DATA),
        'test_dataset_id' => array('type' => MIDAS_DATA),
        'truth_dataset_id' => array('type' => MIDAS_DATA),
        'producer' => array('type' => MIDAS_MANY_TO_ONE,
                            'model' => 'Producer',
                            'module' => $this->moduleName,
                            'parent_column' => 'producer_id',
                            'child_column' => 'producer_id'),
        'config_item' => array('type' => MIDAS_MANY_TO_ONE,
                                  'model' => 'Item',
                                  'parent_column' => 'config_item_id',
                                  'child_column' => 'item_id'),
        'test_dataset_item' => array('type' => MIDAS_MANY_TO_ONE,
                                      'model' => 'Item',
                                      'parent_column' => 'test_dataset_id',
                                      'child_column' => 'item_id'),
        'truth_dataset_item' => array('type' => MIDAS_MANY_TO_ONE,
                                      'model' => 'Item',
                                      'parent_column' => 'truth_dataset_id',
                                      'child_column' => 'item_id'),
        'scalars' => array('type' => MIDAS_ONE_TO_MANY,
                           'model' => 'Scalar',
                           'module' => $this->moduleName,
                           'parent_column' => 'trend_id',
                           'child_column' => 'trend_id')
      );
    $this->initialize();
    }

  abstract public function getMatch($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId);
  abstract public function getAllByParams($params);
  abstract public function getScalars($trend, $startDate = null, $endDate = null, $userId = null, $branch = null);
  abstract public function getTrendsGroupByDatasets($producerDao);

  /**
   * Override the default save to make sure that we explicitly set null values in the database
   */
  public function save($trendDao)
    {
    $trendDao->setExplicitNullFields = true;
    parent::save($trendDao);
    }

  /**
   * If the producer with the matching parameters exists, return it.
   * If not, it will create it and return it.
   */
  public function createIfNeeded($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId)
    {
    $trend = $this->getMatch($producerId, $metricName, $configItemId, $testDatasetId, $truthDatasetId);
    if(!$trend)
      {
      $trend = MidasLoader::newDao('TrendDao', $this->moduleName);
      $trend->setProducerId($producerId);
      $trend->setMetricName($metricName);
      $trend->setDisplayName($metricName);
      $trend->setUnit('');
      if($configItemId != null)
        {
        $trend->setConfigItemId($configItemId);
        }
      if($testDatasetId != null)
        {
        $trend->setTestDatasetId($testDatasetId);
        }
      if($truthDatasetId != null)
        {
        $trend->setTruthDatasetId($truthDatasetId);
        }
      $this->save($trend);
      }
    return $trend;
    }

  /**
   * Delete the trend (deletes all child scalars as well)
   */
  public function delete($trend, $progressDao = null)
    {
    $scalarModel = MidasLoader::loadModel('Scalar', $this->moduleName);
    $notificationModel = MidasLoader::loadModel('ThresholdNotification', $this->moduleName);
    if($progressDao)
      {
      $progressModel = MidasLoader::loadModel('Progress');
      $progressDao->setMessage('Counting scalar points...');
      $progressModel->save($progressDao);
      }
    $scalars = $trend->getScalars();
    if($progressDao)
      {
      $progressDao->setMaximum(count($scalars));
      $progressModel->save($progressDao);
      $i = 0;
      }

    foreach($scalars as $scalar)
      {
      if($progressDao)
        {
        $i++;
        $message = 'Deleting scalars: '.$i.' of '.$progressDao->getMaximum();
        $progressModel->updateProgress($progressDao, $i, $message);
        }
      $scalarModel->delete($scalar);
      }
    $notificationModel->deleteByTrend($trend);
    parent::delete($trend);
    }
  }
