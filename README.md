
PHP Paypal Payment & IPN Integration Class

	       Author:     Amar Pratap Singh & Micah Carrick 
               Email:      amar.pratap@datumradix.com
	       Credit:     Derived from the work of Micah Carrick ://www.micahcarrick.com
	  
	       File:       paypal.class.php & payment.php
	       Version:    2.0.0
	      Copyright:  (c) 2016 Amar Pratap & 2005 - Micah Carrick 
	                   You are free to use, distribute, and modify this software 
	                   under the terms of the GNU General Public License.  See the
	                   included license.txt file.
	       

	  DESCRIPTION:
	 
	       NOTE: This has been modified from the work of www.micahcarrick.com, 
	       The ipn method now uses cURL in place of fsockopen
	 
	       This file provides a neat and simple method to interface with paypal and
	       The paypal Instant Payment Notification (IPN) interface.  This file is
	       NOT intended to make the paypal integration "plug 'n' play". It still
	       requires the developer (that should be you) to understand the paypal
	      process and know the variables you want/need to pass to paypal to
	      achieve what you want.  

	       This class handles the submission of an order to paypal aswell as the
	       processing an Instant Payment Notification.
	   
	       This code is based on that of the php-toolkit from paypal.  I've taken
	       the basic principals and put it in to a class so that it is a little
	       easier--at least for me--to use.  The php-toolkit can be downloaded from
	       http://sourceforge.net/projects/paypal.
	       
	       To submit an order to paypal, have your order form POST to a file with:
	 
	           $p = new paypal_class;
	           $p->add_field('business', 'somebody@domain.com');
	           $p->add_field('first_name', $_POST['first_name']);
	           ... (add all your fields in the same manor)
	           $p->submit_paypal_post();
	 
	       To process an IPN, have your IPN processing file contain:
	 
	           $p = new paypal_class;
	           if ($p->validate_ipn()) {
	           ... (IPN is verified.  Details are in the ipn_data() array)
	           }
	 
	 
	      	          the addition of fake bank accounts and credit cards.
	  
	
