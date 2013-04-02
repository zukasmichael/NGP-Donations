<?php
/* Adapted from http://www.moiagroup.com/archives/using-the-ngp-api/664 & New Media Campaign's Contribution PHP class.
 *
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
 * The first argument is the configuration array, including your NGP credentials string.
 * The final argument is a key value array of constituent information.
 * See the array below for valid keys.
 *
 * $d = new NgpEmailSignup(array(
 *         'credentials'=>'[NGP-generated Credentials String]',
 *         'mainCode'=>'[Main Code]',
 *         'campaignID'=>'[Campaign ID]'
 *     ),
 *     array(
 *         'LastName' => 'Doe',
 *         'FirstName' => 'John',
 *         'Zip' => '27514',
 *         'Email' => 'johndoe@yahoo.com',
 *     )
 * );
 * if ( $d->save() ) {
 *     //Success
 * } else {
 *     if ( $d->hasErrors() ) {
 *         $errors = $d->getErrors(); //array, indicates errors with local data (e.g. missing required fields)
 *     } else if ( $d->hasFault() ) {
 *         $fault = $d->getFault(); //SoapFault Exception, indicates error communicating with SOAP API
 *     } else {
 *         $signupDetails = $d->getResult(); //SimpleXMLElement, may indicate payment transaction failure
 *         $signupStatus = $transactionDetails->VendorResult->Result; //int, status code
 *         $signupMessage = $transactionDetails->VendorResult->Message; //string, status description
 *     }
 * }
 */
class NgpEmailSignup {
    /**
     * @var string Provided by NGP
     */
    protected $credentials;

    /**
     * @var string Campaign ID
     */
    protected $campaignID;

    /**
     * @var array[String] Main Code (?)
     */
    protected $mainCode;

    /**
     * @var array[String] Case sensitive!
     */
    protected $constituentFields;

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
     * @param   string  $configuration    Key-value array of signup configuration names and values:
     *                                    NGP-encrypted string (credentials), Main Code (mainCode) and Campaign ID (campaignID)
     * @param   array   $data           Key-value array of field names and values
     * @return  void
     */
    public function __construct( $configuration, $data = array() ) {
        $this->client = new SoapClient('http://www.myngp.com/ngpapi/APIService.asmx?wsdl');
        $this->credentials = $configuration['credentials'];
        $this->mainCode = $configuration['mainCode'];
        $this->campaignID = $configuration['campaignID'];
        // http://www.myngp.com/ngpapi/transactions/Contact/Contact.xsd
        $this->constituentFields = array(
            'lastName' => '', //REQUIRED
            'firstName' => '', //REQUIRED
            'middleName' => '',
            'prefix' => '',
            'suffix' => '',
            'address1' => '', //REQUIRED
            'address2' => '',
            'city' => '',
            'state' => '',
            'zip' => '', //REQUIRED
            'salutation' => '',
            'email' => '',
            'homePhone' => '',
            'workPhone' => '',
            'workExtension' => '',
            'mobilePhone' => '',
            'smsOptIn' => true, //bool
            'employer' => '',
            'occupation' => '',
        );
        $this->allFields = array_merge(
            $this->constituentFields,
            $this->paymentFields,
            $data
        );
        $this->requiredFields = array(
            'Email'
        );
    }

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
            'credentials' => $this->credentials,
            'RequestXML' => $this->generateXml(),
            'transType' => 'contactWithEmailSet'
        );
        try {
            $res = $this->client->processRequestWithCreds($args);
            $this->result = new SimpleXMLElement($res->processRequestWithCredsResult);
            if($this->result->Message=='An unexpected error has occurred.') { return false; } else {
                return (int)$this->result->VendorResult->Result === 0;
            }
        } catch ( SoapFault $e ) {
            $this->fault = $e;
            return false;
        }
    }

    /**
     * Get transaction result details
     * @return SimpleXMLElement
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Is transaction data valid?
     * @return bool
     */
    public function isValid() {
        //Check requiredness
        foreach( $this->requiredFields as $field ) {
            if ( !isset($this->allFields[$field]) || empty($this->allFields[$field]) ) {
                $this->errors[] = "$field is required";
            }
        }

        return empty($this->errors);
    }

    /**
     * Generate XML payload
     * @return string
     */
    public function generateXml() {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= "<contactWithEmailSet>";
        $xml .= "<contact>";
        $xml .= ( isset( $this->constituentFields['LastName'] ) && !empty( $this->constituentFields['LastName'] ) ) ? "<lastName>{".$this->constituentFields['LastName']."}</lastName>" : '<lastName />';
        $xml .= ( isset( $this->constituentFields['FirstName'] ) && !empty( $this->constituentFields['FirstName'] ) ) ? "<firstName>{".$this->constituentFields['FirstName']."}</firstName>" : '<firstName />';
        $xml .= ( isset( $this->constituentFields['Zip'] ) && !empty( $this->constituentFields['Zip'] ) ) ? "<zip>{".$this->constituentFields['Zip']."}</zip>" : '<zip />';
        $xml .= ( isset( $this->constituentFields['Email'] ) && !empty( $this->constituentFields['Email'] ) ) ? "<email>{".$this->constituentFields['Email']."}</email>" : '<email />';
        $xml .= "</contact>";
        $xml .= "<mainCode>{$this->mainCode}</mainCode>";
        $xml .= "<optIn>1</optIn>";
        $xml .= "<campaignID>{$this->campaignID}</campaignID>";
        $xml .= "</contactWithEmailSet>";
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
}
