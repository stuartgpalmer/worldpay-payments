<?php


	Final Class eventWorldpay_payments_pn extends Event{

		public static function about()
		{					
			return array(
						 'name' => 'Worldpay Payments: Save Payment Notification data',
						 'author' => array('name' => 'Stuart Palmer',
											 'website' => 'http://www.eyes-down.net',
											 'email' => 'stuart@eyes-down.net'),
						 'version' => '0.0.1',
						 'release-date' => '2011-09-21',
					);						 
		}
		
		public function __construct(&$parent)
		{
			parent::__construct($parent);
			$this->_driver = Symphony::Engine()->ExtensionManager->create('worldpay_payments');
		}
		
		public function load()
		{

			# Run through the posted array 
			foreach ($_POST as $key => $value)
			{ 
				# If magic quotes is enabled strip slashes 
				if (get_magic_quotes_gpc()) 
				{ 
					$_POST[$key] = stripslashes($value); 
					$value = stripslashes($value); 
				} 
				$value = urlencode($value); 
				# Add the value to the request parameter 
				$req .= "&$key=$value";
			} 
			
			
			# Check that we have data and that it’s VERIFIED
			if (! empty($_POST)) return $this->__trigger();
			return NULL;
		}

		public static function documentation()
		{
			$docs = array();
			$docs[] = '
<p>Documentation here</p>
';
			return implode("\n", $docs);
		}
		
		protected function __trigger()
		{			
			# Array of valid variables from Worldpay
			$valid_variables = array(
				'cartId',
				'amount',
				'cost',
				'desc',
				'currency',
				'name',
				'email',
				'transId',
				'transStatus',
				'transTime',
			);
			
			$required_variables = array(
				'cartId',
				'amount',
				'cost',
				'desc',
				'currency',
				'name',
				'email',
				'transId',
				'transStatus',
				'transTime',
			);
			
			# Find any matches in the $_POST data
			$matches = array();
			foreach ($_POST as $key => $val)
			{
				if (in_array($key, $valid_variables)) $matches[$key] = utf8_encode(General::sanitize($val));
			}

			# Output the matches in XML
			$output = new XMLElement('worldpay-payments-pn');
			$log = array();

			if ( ! empty($matches))
			{
				foreach ($matches as $key => $val)
				{
					$output->appendChild(new XMLElement($key, $val));
					# If in required vars, add to log
					if (in_array($key, $required_variables))
					{
						if ($key == 'transTime') $val = strftime('%Y-%m-%d %H:%M:%S', strtotime($val));
						$log[$key] = $val;
					}
				}
				
				# Reconcile with original entry
				$entry_id = $log['cartId'];
				
				$entryManager = new EntryManager(Symphony::Engine());
				$fieldManager = new FieldManager(Symphony::Engine());
				
				$entries = $entryManager->fetch($entry_id, null, null, null, null, null, false, true);
				if (count($entries) > 0)
				{
					$entry = $entries[0];
					$section_id = $entry->get('section_id');
					$fields = Symphony::Database()->fetch("
						SELECT `id`, `label` FROM `tbl_fields` WHERE `parent_section` = '$section_id'
					");
			
					foreach ($fields as $field)
					{
						$label = $field['label'];
						# Check if entry fields match values returned from Worldpay
						if (in_array($label, $valid_variables))
						{
							$value = $log[$label];
							$entry->setData($field['id'], array(
									'handle'	=> Lang::createHandle($value),
									'value'	 => $value
								)
							);
						}
					}	
					# Transfom and move out
					$entry->commit();
					$output->setAttribute('result', 'success');
					$output->appendChild(new XMLElement('message', 'Worldpay data logged and reconciled.'));
				} else {  
					$output->setAttribute('result', 'error');
					$output->appendChild(new XMLElement('message', 'No matching entry, could not reconcile payment data.'));
				}
				
				# Save log, delete previous IPN logs with same invoice number
				Symphony::Database()->query("
					DELETE FROM
						`tbl_worldpaypayments_logs`
					WHERE
						`txn_id` = '{$log['txn_id']}' AND
						`payment_status` = '{$log['payment_status']}'
				");
				Symphony::Database()->insert($log, 'tbl_worldpaypayments_logs');
			}  
			return $output;
		}
	}