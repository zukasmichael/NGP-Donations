<?php
/**
 * NGP Donation
 *
 * @author      Josh Lockhart <josh@newmediacampaigns.com>
 * @copyright   2012 New Media Campaigns
 * @link        http://www.newmediacampaigns.com
 * @version     1.0.0
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * USAGE:
 *
 * The first argument is your NGP credentials string. The second
 * argument is a boolean value that determines if the volunteer
 * should be notified by email after his contribution is accepted.
 * The final argument is a key value array of volunteer, Contribution,
 * and Payment details. See the array below for valid keys.
 *
 * $d = new NgpDonation('credentials-string', false, array(
 *     'LastName' => 'Doe',
 *     'FirstName' => 'John',
 *     'Address1' => '100 Elm Street',
 *     'Zip' => '27514',
 *     'Cycle' => 2012,
 *     'Amount' => 10,
 *     'CreditCardNumber' => '4111111111111111',
 *     'ExpYear' => '13',
 *     'ExpMonth' => '02'
 * ));
 * if ( $d->save() ) {
 *     //Success
 * } else {
 *     if ( $d->hasErrors() ) {
 *         $errors = $d->getErrors(); //array, indicates errors with local data (e.g. missing required fields)
 *     } else if ( $d->hasFault() ) {
 *         $fault = $d->getFault(); //SoapFault Exception, indicates error communicating with SOAP API
 *     } else {
 *         $transactionDetails = $d->getResult(); //SimpleXMLElement, may indicate payment transaction failure
 *         $transactionStatus = $transactionDetails->VendorResult->Result; //int, status code
 *         $transactionMessage = $transactionDetails->VendorResult->Message; //string, status description
 *     }
 * }
 */
class NgpDonation {
    /**
     * @var string Provided by NGP
     */
    protected $credentials;

    /**
     * @var string Send email to volunteer after donation accepted?
     */
    protected $sendEmail;

    /**
     * @var array[String] Case sensitive!
     */
    protected $allFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $volunteerFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $contributionFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $paymentFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $requiredFields;

    /**
     * @var array[String]
     */
    protected $errors;

    /**
     * @var SoapClient
     */
    protected $client;

    /**
     * @var SoapFault
     */
    protected $fault;

    /**
     * @var SimpleXMLElement
     */
    protected $result;

    /**
     * Constructor
     *
     * @param   string  $credentials    Your NGP encrypted credentials string
     * @param   bool    $sendEmail      Notify volunteer after donation accepted?
     * @param   array   $data           Key-value array of field names and values
     * @return  void
    */
    public function __construct( $credentials, $sendEmail = false, $data = array() ) {
        $this->client = new SoapClient('http://services.myngp.com/ngpservices/VolunteerSignUpService.asmx?wsdl');
        $this->credentials = $credentials;
        $this->sendEmail = $sendEmail;
        $this->volunteerFields = array(
            'LastName' => '', //REQUIRED
            'FirstName' => '', //REQUIRED
            'MiddleName' => '',
            'Prefix' => '',
            'Suffix' => '',
            'Address1' => '', //REQUIRED
            'Address2' => '',
            'Address3' => '',
            'City' => '',
            'State' => '',
            'Zip' => '', //REQUIRED
            'Salutation' => '',
            'Email' => '',
            'HomePhone' => '',
            'WorkPhone' => '',
            'WorkExtension' => '',
            'FaxPhone' => '',
            'Employer' => '',
            'Occupation' => '',
            'OptIn' => true, //bool
            'MainType' => 'I',
            'Organization' => '',
        );
        $this->volunteerFields = array(
			'Code' => 'AA',
			'note' => '',
        );
        $this->volunteerFields = array(
			'Code' => 'BB',
			'note' => '',
        );

	    /**
	     * Set required fields
	     * @param array[String] Case sensitive numeric array of field names
	     * @return void
	     */
	    public function setRequiredFields( $fields ) {
	        $this->requiredFields = $fields;
	    }

	    /**
	     * Add required fields
	     * @param array[String] Case sensitive numeric array of field names
	     * @return void
	     */
	    public function addRequiredFields( $fields ) {
	        $this->requiredFields = array_merge($this->requiredFields, $fields);
	    }

	    /**
	     * Save email signup
	     *
	     * Returns (int)0 on success, (bool)false on failure. If this returns an integer other
	     * than zero, inspect the transaction result with `getResult()`. If this returns false,
	     * you should check for data errors with `getErrors()` or an API fault with `getFault()`.
	     *
	     * @return bool
	    */
	    public function save() {
	        if ( $this->isValid() === false ) {
	            return false;
	        }
	        $args = array(
	            'RequestXML' => $this->generateXml(),
	            'transType' => 'ContactSetICampaigns',
	            'credentialString' => $this->credentialString
	        );
	        // WP_Http
	        $headers = array(
	            'User-agent': 'RevMsg Wordpress PLugin (support@revmsg.com)',
	        );
	        $result = $request->request('http://www.myngp.com/ngpapi/APIService.asmx/processRequestWithCreds', array(
	            'method' => 'POST',
	            'body' => $args,
	            'headers' => $headers
	        ));
	        var_dump($result);
	    }
	    public function generateXml() {
	        $xml = '<VolunteerSignUp>';
	        $xml .= '<ContactInfo>';
	        foreach ( $this->contributorFields as $name => $defaultValue ) {
	            if ( is_bool($this->allFields[$name]) ) {
	                $this->allFields[$name] = $this->allFields[$name] ? 'true' : 'false';
	            }
	            if ( !empty($this->allFields[$name]) ) {
	                $xml .= sprintf('<%s>%s</%s>', $name, $this->allFields[$name], $name);
	            } else {
	                $xml .= sprintf('<%s/>', $name);
	            }
	        }
	        $xml .= '</ContactInfo>';

	        $xml .= '<VolunteerInfo>';
	        foreach ( $this->Code as $name => $defaultValue ) {
	            if ( is_bool($this->allFields[$name]) ) {
	                $this->allFields[$name] = $this->allFields[$name] ? 'true' : 'false';
	            }
	            if ( !empty($this->allFields[$name]) ) {
	                $xml .= sprintf('<%s>%s</%s>', $name, $this->allFields[$name], $name);
	            } else {
	                $xml .= sprintf('<%s/>', $name);
	            }
	        }
	        $xml .= '</VolunteerInfo>';
	        $xml .= '</VolunteerSignUp>';
	        return $xml;
	    }
	    /**
	     * Get errors
	     * return array[String]|null
	     */
	    public function getErrors() {
	        return $this->errors;
	    }

	    /**
	     * Has errors?
	     * @return bool
	     */
	    public function hasErrors() {
	        return !empty($this->errors);
	    }

	    /**
	     * Get last fault
	     * @return SoapFault|null
	     */
	    public function getFault() {
	        return $this->fault;
	    }

	    /**
	     * Has fault?
	     * @return bool
	     */
	    public function hasFault() {
	        return !empty($this->fault);
	    }
?>