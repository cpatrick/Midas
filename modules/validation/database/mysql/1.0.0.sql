CREATE TABLE IF NOT EXISTS `validation_dashboard` (
  `dashboard_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `truthfolder_id` bigint(20) DEFAULT NULL,
  `testingfolder_id` bigint(20) DEFAULT NULL,
  `trainingfolder_id` bigint(20) DEFAULT NULL,
  `min` double DEFAULT NULL,
  `max` double DEFAULT NULL,
  `metric_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`dashboard_id`)
);

CREATE TABLE IF NOT EXISTS `validation_scalarresult` (
  `scalarresult_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `folder_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `value` double DEFAULT NULL,
  PRIMARY KEY (`scalarresult_id`)
);


CREATE TABLE IF NOT EXISTS `validation_dashboard2folder` (
  `dashboard2folder_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint(20) NOT NULL,
  `folder_id` bigint(20) NOT NULL,
  PRIMARY KEY (`dashboard2folder_id`)
);

CREATE TABLE IF NOT EXISTS `validation_dashboard2scalarresult` (
  `dashboard2scalarresult_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint(20) NOT NULL,
  `scalarresult_id` bigint(20) NOT NULL,
  PRIMARY KEY (`dashboard2scalarresult_id`)
);
