# Worldpay Payments #
 
Version: 0.0.1  
Author: [Stuart Palmer](http://www.eyes-down.net)  
Build Date: 2011-09-23  
Compatibility: Symphony 2.2.3

The * Worldpay Payments* extension allows you to reconcile and track Worldpay payments.

## Installation ##
 
1. Upload the `worldpay_payments` folder in this archive to your Symphony
	 `extensions` folder.
 
2. Enable it by selecting the * Worldpay Payments*, choose Enable from the
	 with-selected menu, then click Apply.
 
## Usage ##

The extension includes a "Save Payment Notification data" Event that logs transactions and reconciles returned data with the newly created entry.

### Event: Save Worldpay Payment Notification data ###

This event is used to deal with data returned Worldpay

1. Saves the transaction details to the transaction log.
2. Reconciles the data return by Worldpay with matching fields in the originating entry.

A number of default fields are logged in the transaction log. They are:

* `cartId`
* `amount`
* `cost`
* `desc`
* `currency`
* `name`
* `email`
* `transId`
* `transStatus`
* `transTime`

Any of these fields can be saved back into the original entry by including a field in the matching section with the *exact* same name. Your payment notification data *must* include a `cartId ` field that matches an entry ID in your site otherwise the data will be discarded.

*Note: for the event to work you'll need to make sure the your Payment Notification URL points to the page that has this event attached.*

### Valid Variables ###

* `cartId`
* `amount`
* `cost`
* `desc`
* `currency`
* `name`
* `email`
* `transId`
* `transStatus`
* `transTime`

## Notes ##

No notes as yet

## Changelog ##

**0.0.1**

Original commit