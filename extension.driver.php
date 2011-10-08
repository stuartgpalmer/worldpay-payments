<?php

  require_once(TOOLKIT . '/class.sectionmanager.php');
  require_once(TOOLKIT . '/class.entrymanager.php');
  require_once(TOOLKIT . '/class.fieldmanager.php');

	Class extension_worldpay_payments extends Extension
	{
	/*-------------------------------------------------------------------------
		Extension definition
	-------------------------------------------------------------------------*/
		public function about()
		{
			return array('name' => 'Worldpay Payments',
						 'version' => '0.0.1',
						 'release-date' => '2011-09-21',
						 'author' => array('name' => 'Stuart Palmer',
										   'website' => 'http://www.eyes-down.net/',
										   'email' => 'stuart@eyes-down.net'),
 						 'description' => 'Allows you to process and track Worldpay transactions.'
				 		);
		}

		public function uninstall()
		{
			# Remove tables
			Symphony::Database()->query("DROP TABLE `tbl_worldpaypayments_logs`");

			# Remove preferences
			Symphony::Configuration()->remove('worldpay-payments');
			Administration::instance()->saveConfig();
		}

		public function install()
		{
		  # Create tables
		  Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_worldpaypayments_logs` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`transTime` datetime NOT NULL,
					`desc` varchar(255) NOT NULL,
					`transStatus` varchar(255) NOT NULL,
					`name` varchar(255) NOT NULL,
					`email` varchar(255) NOT NULL,
					`currency` varchar(3) NOT NULL,
					`cost` decimal(10,2) NOT NULL,
					`amount` decimal(10,2) NOT NULL,
					`transId` varchar(255) NOT NULL,
					`cartId` varchar(255) NOT NULL,
					PRIMARY KEY (`id`)
				)
			");
		  return true;
		}

		public function getSubscribedDelegates()
		{
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => 'save_preferences'
				),
				array(
					'page' => '/system/preferences/success/',
					'delegate' => 'Save',
					'callback' => 'save_preferences'
				),
				array(
					'page'		=> '/blueprints/events/edit/',
					'delegate'	=> 'AppendEventFilter',
					'callback'	=> 'add_filter_to_event_editor'
				),
			);
		}

		/*-------------------------------------------------------------------------
			Navigation
		-------------------------------------------------------------------------*/

		public function fetchNavigation()
		{
		  $nav = array();
		  $nav[] = array(
				'location'	=> 261,
				'name'		=> 'Worldpay Payments',
				'children'	=> array(
					array(
						'name'		=> 'Transactions',
						'link'		=> '/logs/',
						'limit'   => 'developer',
					)
				)
			);
      return $nav;
		}

  	/*-------------------------------------------------------------------------
  		Helpers
  	-------------------------------------------------------------------------*/

		public function _count_logs()
		{
			return (integer)Symphony::Database()->fetchVar('total', 0, "
				SELECT
					COUNT(l.id) AS `total`
				FROM
					`tbl_worldpaypayments_logs` AS l
			");
		}


		public function _get_logs_by_page($page, $per_page)
		{
			$start = ($page - 1) * $per_page;

			return Symphony::Database()->fetch("
				SELECT
					l.*
				FROM
					`tbl_worldpaypayments_logs` AS l
				ORDER BY
					l.transTime DESC
				LIMIT {$start}, {$per_page}
			");
		}

		public function _get_logs()
		{
			return Symphony::Database()->fetch("
				SELECT
					l.*
				FROM
					`tbl_worldpaypayments_logs` AS l
				ORDER BY
					l.payment_date DESC
			");
		}

		public function _get_log($log_id) {
			return Symphony::Database()->fetchRow(0, "
				SELECT
					l.*
				FROM
					`tbl_worldpaypayments_logs` AS l
				WHERE
					l.id = '{$log_id}'
				LIMIT 1
			");
		}

	}