<?php
	/*******************************************************************************
	 *                      PHP Paypal IPN Integration Class
	 *******************************************************************************
	 *      Author:     Amar Pratap Singh
	 *      Email:      amar.pratap@datumradix.com
	 *      Credit:     Derived from the work of Micah Carrick ://www.micahcarrick.com
	 *
	 *      File:       paypal.class.php
	 *      Version:    2.0.0
	 *      Copyright:  (c) 2016 Amar Pratap & 2005 - Micah Carrick 
	 *                  You are free to use, distribute, and modify this software 
	 *                  under the terms of the GNU General Public License.  See the
	 *                  included license.txt file.
	 *      
	 *******************************************************************************
	 	 *
	 *******************************************************************************
	 *  DESCRIPTION:
	 *
	 *      NOTE: This has been modified from the work of www.micahcarrick.com, the ipn method now uses cURL in place of fsockopen
	 *
	 *      This file provides a neat and simple method to interface with paypal and
	 *      The paypal Instant Payment Notification (IPN) interface.  This file is
	 *      NOT intended to make the paypal integration "plug 'n' play". It still
	 *      requires the developer (that should be you) to understand the paypal
	 *      process and know the variables you want/need to pass to paypal to
	 *      achieve what you want.  
	 *
	 *      This class handles the submission of an order to paypal aswell as the
	 *      processing an Instant Payment Notification.
	 *  
	 *      This code is based on that of the php-toolkit from paypal.  I've taken
	 *      the basic principals and put it in to a class so that it is a little
	 *      easier--at least for me--to use.  The php-toolkit can be downloaded from
	 *      http://sourceforge.net/projects/paypal.
	 *      
	 *      To submit an order to paypal, have your order form POST to a file with:
	 *
	 *          $p = new paypal_class;
	 *          $p->add_field('business', 'somebody@domain.com');
	 *          $p->add_field('first_name', $_POST['first_name']);
	 *          ... (add all your fields in the same manor)
	 *          $p->submit_paypal_post();
	 *
	 *      To process an IPN, have your IPN processing file contain:
	 *
	 *          $p = new paypal_class;
	 *          if ($p->validate_ipn()) {
	 *          ... (IPN is verified.  Details are in the ipn_data() array)
	 *          }
	 *
	 *
	 *     	 *         the addition of fake bank accounts and credit cards.
	 * 
	 *******************************************************************************
	*/
	
	class paypal_class {
			
		 var $last_error;                 // holds the last error encountered
		 
		 var $ipn_log;                    // bool: log IPN results to text file?
		 
		 var $ipn_log_file;               // filename of the IPN log
		 var $ipn_response;               // holds the IPN response from paypal   
		 var $ipn_data = array();         // array contains the POST values for IPN
		 
		 var $fields = array();           // array holds the fields to submit to paypal
		 
		 const DEBUG = 1;
                // Set to 0 once you're ready to go live
                 const SANDBOX = 1;
                 const LOG_FILE = "./ipn_results.log";
	
		 
		 function paypal_class() {
				 
				// initialization constructor.  Called when class is created.
				
				$this->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
				
				$this->last_error = '';
				
				$this->ipn_log = true; 
				$this->ipn_response = '';
				
				// populate $fields array with a few default values.  See the paypal
				// documentation for a list of fields and their data types. These defaul
				// values can be overwritten by the calling script.
	
				$this->add_field('rm','2');           // Return method = POST
				$this->add_field('cmd','_xclick'); 
				
		 }
		 
		 function add_field($field, $value) {
				
				// adds a key=>value pair to the fields array, which is what will be 
				// sent to paypal as POST variables.  If the value is already in the 
				// array, it will be overwritten.
							
				$this->fields["$field"] = $value;
		 }
	
		 function submit_paypal_post() {
	 
				// this function actually generates an entire HTML page consisting of
				// a form with hidden elements which is submitted to paypal via the 
				// BODY element's onLoad attribute.  We do this so that you can validate
				// any POST vars from you custom form before submitting to paypal.  So 
				// basically, you'll have your own form which is submitted to your script
				// to validate the data, which in turn calls this function to create
				// another hidden form and submit to paypal.
	 
				// The user will briefly see a message on the screen that reads:
				// "Please wait, your order is being processed..." and then immediately
				// is redirected to paypal.
	
				echo "<html>\n";
				echo "<head><title>Processing Payment...</title></head>\n";
				echo "<body onLoad=\"document.forms['paypal_form'].submit();\">\n";
				echo "<center><h2>Please wait, your order is being processed and you";
				echo " will be redirected to the Paypal website.</h2></center>\n";
				echo "<form method=\"post\" name=\"paypal_form\" ";
				echo "action=\"".$this->paypal_url."\">\n";
	
				foreach ($this->fields as $name => $value) {
					 echo "<input type=\"hidden\" name=\"$name\" value=\"$value\"/>\n";
				}
				echo "<center><br/><br/>If you are not automatically redirected to ";
				echo "paypal within 5 seconds...<br/><br/>\n";
				echo "<input type=\"submit\" value=\"Click Here\"></center>\n";
				
				echo "</form>\n";
				echo "</body></html>\n";
			
		 }
	 
		 function validate_ipn() {
	
				
	
				// generate the post string from the _POST vars aswell as load the
				// _POST vars into an arry so we can play with them from the calling
				// script.
				$post_string = '';    
				foreach ($_POST as $field=>$value) { 
					 $this->ipn_data["$field"] = $value;
					 
				}

				$raw_post_data = file_get_contents('php://input');
				$raw_post_array = explode('&', $raw_post_data);
				$myPost = array();
				foreach ($raw_post_array as $keyval) {
					$keyval = explode ('=', $keyval);
					if (count($keyval) == 2)
						$myPost[$keyval[0]] = urldecode($keyval[1]);
				}
				// read the post from PayPal system and add 'cmd'
				$req = 'cmd=_notify-validate';
				if(function_exists('get_magic_quotes_gpc')) {
					$get_magic_quotes_exists = true;
				}
				foreach ($myPost as $key => $value) {
					if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
						$value = urlencode(stripslashes($value));
					} else {
						$value = urlencode($value);
					}
					$req .= "&$key=$value";
				}
				// Post IPN data back to PayPal to validate the IPN data is genuine
				// Without this step anyone can fake IPN data
				if(SANDBOX == true) {
					$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
				} else {
					$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
				}
				$ch = curl_init($paypal_url);
				if ($ch == FALSE) {
					return FALSE;
				}
				curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
				if(DEBUG == true) {
					curl_setopt($ch, CURLOPT_HEADER, 1);
					curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
				}
				// CONFIG: Optional proxy configuration
				//curl_setopt($ch, CURLOPT_PROXY, $proxy);
				//curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
				// Set TCP timeout to 30 seconds
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
				// CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
				// of the certificate as shown below. Ensure the file is readable by the webserver.
				// This is mandatory for some environments.
				//$cert = __DIR__ . "./cacert.pem";
				//curl_setopt($ch, CURLOPT_CAINFO, $cert);
				$res = curl_exec($ch);
				if (curl_errno($ch) != 0) // cURL error
					{
					if(DEBUG == true) {	
						error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
					}
					curl_close($ch);
					exit;
				} else {
						// Log the entire HTTP response if debug is switched on.
						if(DEBUG == true) {
							error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
							error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
						}
						curl_close($ch);
				}
				// Inspect IPN validation result and act accordingly
				// Split response headers and payload, a better way for strcmp
				$tokens = explode("\r\n\r\n", trim($res));
				$res = trim(end($tokens));
	
				
				
				// if (eregi("VERIFIED",$this->ipn_response))  // deprecated
				if (strcmp ($res, "VERIFIED") == 0) {
					
					error_log(date('[Y-m-d H:i e] '). "Verified IPN: $req ". PHP_EOL, 3, LOG_FILE);
					return true;       
					 
				} else {
						
					// Invalid IPN transaction.  Check the log for details.
					error_log(date('[Y-m-d H:i e] '). "Invalid IPN: $req" . PHP_EOL, 3, LOG_FILE);
					return false;
					 
				}
				
		 }
		 
		 
	
		 function dump_fields() {
	 
				// Used for debugging, this function will output all the field/value pairs
				// that are currently defined in the instance of the class using the
				// add_field() function.
				
				echo "<h3>paypal_class->dump_fields() Output:</h3>";
				echo "<table width=\"95%\" border=\"1\" cellpadding=\"2\" cellspacing=\"0\">
							<tr>
								 <td bgcolor=\"black\"><b><font color=\"white\">Field Name</font></b></td>
								 <td bgcolor=\"black\"><b><font color=\"white\">Value</font></b></td>
							</tr>"; 
				
				ksort($this->fields);
				foreach ($this->fields as $key => $value) {
					 echo "<tr><td>$key</td><td>".urldecode($value)."&nbsp;</td></tr>";
				}
	 
				echo "</table><br>"; 
		 }
	}
?>
