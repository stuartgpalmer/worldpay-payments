<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class contentExtensionWorldpay_paymentsLogs extends AdministrationPage {
		protected $_errors = array();
		protected $_action = '';
		protected $_status = '';
		protected $_driver = NULL;
		
		public function __construct(&$parent)
		{
			parent::__construct($parent);
			$this->_driver = Administration::instance()->ExtensionManager->create('worldpay_payments');
		}
		
		public function __actionIndex()
		{
			$checked = @array_keys($_POST['items']);

			if (is_array($checked) and !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						foreach ($checked as $log_id) {
							Symphony::Database()->query("
								DELETE FROM
									`tbl_worldpaypayments_logs`
								WHERE
									`id` = {$log_id}
							");
						}

						redirect(URL . '/symphony/extension/worldpay_payments/logs/');
						break;
				}
			}
		}
		
		public function __viewIndex()
		{		
			$this->setPageType('table');
			$this->setTitle('Symphony &ndash; Worldpay Payment Transactions');
			$this->appendSubheading('Logs');
			$this->addStylesheetToHead(URL . '/extensions/worldpay_payments/assets/logs.css', 'screen', 81);
			
			$per_page = 20;
			$page = (@(integer)$_GET['pg'] > 1 ? (integer)$_GET['pg'] : 1);
			$logs = $this->_driver->_get_logs_by_page($page, $per_page);
			$start = max(1, (($page - 1) * $per_page));
			$end = ($start == 1 ? $per_page : $start + count($logs));
			$total = $this->_driver->_count_logs();
			$pages = ceil($total / $per_page);
								
			$sectionManager = new SectionManager(Administration::instance());
			$entryManager = new EntryManager(Administration::instance());
			
			$th = array(
				array('Invoice/Entry', 'col'),
				array('Date', 'col'),
				array('Payment Status', 'col'),
				array('Name', 'col'),
				array('Email', 'col'),
				array('Currency', 'col'),
				array('Gross', 'col'),
				array('Transaction ID', 'col'),
			);
						
			if ( ! is_array($logs) or empty($logs)) {
				$tb = array(
					Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', NULL, count($th))))
				);

			} else {
				foreach ($logs as $log)
				{
					$col = array();
					# Spit out $log_name vars
					extract($log, EXTR_PREFIX_ALL, 'log');
					
					# Get the entry/section data
					$entries = $entryManager->fetch($log_invoice, NULL, NULL, NULL, NULL, NULL, FALSE, TRUE);
					$entry = $entries[0];
					if (isset($entry))
					{
						$section_id = $entry->get('section_id');
						$section = $sectionManager->fetch($section_id);
						$column = array_shift($section->fetchFields());
						$data = $entry->getData($column->get('id'));
						# Build link to parent section
						$link = URL . '/symphony/publish/' . $section->get('handle') . '/edit/' . $entry->get('id') . '/';

						# Date
						$col[] = Widget::TableData( Widget::Anchor( General::sanitize($log_cartId), $link ) );
					} else {
						$col[] = Widget::TableData( General::sanitize($log_cartId) );
					}
					$col[0]->appendChild(Widget::Input("items[{$log_id}]", NULL, 'checkbox'));
					
					if ( ! empty($log_transTime)) $col[] = Widget::TableData( DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($log_transTime)) );
					else $col[] = Widget::TableData('None', 'inactive');
					
					if ( ! empty($log_transStatus)) $col[] = Widget::TableData(General::sanitize($log_transStatus));
					else $col[] = Widget::TableData('None', 'inactive');
					
					if ( ! empty($log_name)) $col[] = Widget::TableData(General::sanitize($log_name));
					else $col[] = Widget::TableData('None', 'inactive');
					
					if ( ! empty($log_email)) $col[] = Widget::TableData(General::sanitize($log_email));
					else $col[] = Widget::TableData('None', 'inactive');
										
					if ( ! empty($log_currency)) $col[] = Widget::TableData(General::sanitize($log_currency));
 					else $col[] = Widget::TableData('None', 'inactive');
										
					if ( ! empty($log_amount)) $col[] = Widget::TableData(General::sanitize($log_amount));
 					else $col[] = Widget::TableData('None', 'inactive');
					
					if ( ! empty($log_transId)) $col[] = Widget::TableData(General::sanitize($log_transId));
 					else $col[] = Widget::TableData('None', 'inactive');
					
					$tr = Widget::TableRow($col);
					if ($log_payment_status == 'Denied') $tr->setAttribute('class', 'denied');
					$tb[] = $tr;
				}
			}

			$table = Widget::Table(
				Widget::TableHead($th), NULL, 
				Widget::TableBody($tb), 'selectable'
			);
			
			$this->Form->appendChild($table);
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, FALSE, 'With Selected...'),
				array('delete', FALSE, 'Delete')									
			);

			$actions->appendChild(Widget::Select('with-selected', $options));
			$actions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));
			
			$this->Form->appendChild($actions);
			
			# Pagination:
			if ($pages > 1) {
				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'page');
				
				## First
				$li = new XMLElement('li');				
				if ($page > 1) {
					$li->appendChild(
						Widget::Anchor('First', Administration::instance()->getCurrentPageURL() . '?pg=1')
					);					
				} else {
					$li->setValue('First');
				}				
				$ul->appendChild($li);
				
				## Previous
				$li = new XMLElement('li');				
				if ($page > 1) {
					$li->appendChild(
						Widget::Anchor('&larr; Previous', Administration::instance()->getCurrentPageURL(). '?pg=' . ($page - 1))
					);					
				} else {
					$li->setValue('&larr; Previous');
				}				
				$ul->appendChild($li);

				## Summary
				$li = new XMLElement('li', 'Page ' . $page . ' of ' . max($page, $pages));				
				$li->setAttribute('title', 'Viewing ' . $start . ' - ' . $end . ' of ' . $total . ' entries');				
				$ul->appendChild($li);

				## Next
				$li = new XMLElement('li');				
				if ($page < $pages) {
					$li->appendChild(
						Widget::Anchor('Next &rarr;', Administration::instance()->getCurrentPageURL(). '?pg=' . ($page + 1))
					);					
				} else {
					$li->setValue('Next &rarr;');
				}				
				$ul->appendChild($li);

				## Last
				$li = new XMLElement('li');				
				if ($page < $pages) {
					$li->appendChild(
						Widget::Anchor('Last', Administration::instance()->getCurrentPageURL(). '?pg=' . $pages)
					);					
				} else {
					$li->setValue('Last');
				}				
				$ul->appendChild($li);
				$this->Form->appendChild($ul);	
			}
		}
	}