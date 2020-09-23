<?php
namespace SKien\Sepa;

/**
 * Class representing transaction (used for CreditTransferTx and DirectDebitTx)
 * 
 * uses helpers and const from trait SepaHelper
 * @see SepaHelper
 *
 * #### History
 * - *2020-02-18*   initial version.
 * - *2020-05-21*   added multi country validation.
 * - *2020-05-21*   renamed namespace to fit PSR-4 recommendations for autoloading.
 * - *2020-07-22*   added missing PHP 7.4 type hints / docBlock changes 
 * - *2020-09-23*   splited validate() into validateXXX()-Methods 
 * 
 * @package SKien/Sepa
 * @since 1.0.0
 * @version 1.2.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaTxInf
{
    use SepaHelper;
    
    /** @var string Type (Sepa::CDD or Sepa::CCT) */
    protected string $type = '';
    /** @var string Full name (lastname, firstname; company name; ...) */
    protected string $strName = '';
    /** @var string IBAN */
    protected string $strIBAN = '';
    /** @var string BIC */
    protected string $strBIC = '';
    /** @var string Mandate identification (only debit) */
    protected string $strMandateId = '';
    /** @var string Date when the mandate identification signed (only debit) (format YYYY-MM-DD) */
    protected string $strDateOfSignature = '';
    /** @var string Ultimate debitor name (information purpose only) */
    protected string $strUltimateName = '';
    /** @var string Payment id */
    protected string $strPaymentId = '';
    /** @var string Description */
    protected string $strDescription = '';
    /** @var float Value of the transaction in EUR */
    protected float $dblValue = 0.0;
    
    /**
     * Create transaction info.
     * @param string $type (Sepa::CDD or Sepa::CCT)
     */
    public function __construct(string $type)
    {
        // invalid type causes E_USER_ERROR
        if ($this->isValidType($type)) {
            $this->type = $type;
        }
    }
    
    /**
     * Validate object
     * @return int
     */
    public function validate() : int
    {
        $iErr = $this->validateIBAN() | $this->validateBIC() | $this->validateMandatory();
        
        // create payment id if empty so far!
        if (empty($this->strPaymentId)) {
            $this->strPaymentId = ($this->type == Sepa::CDD ? self::createUID() : 'NOTPROVIDED');
        }
        return $iErr;
    }
    
    /**
     * Get error message for error code.
     * There can be multiple errors on one single transaction.
     * @param int $iError
     * @param string $strLF     Separator for multi errors (default: PHP_EOL; posible values: '<br />', '; ', ...) 
     * @return string
     */
    public function errorMsg(int $iError, string $strLF = PHP_EOL) : string 
    {
        return Sepa::errorMsgTxInf($iError, $strLF);
    }
    
    /**
     * Set properties through associative array 
     * @param array $aProperties
     */
    public function fromArray(array $aProperties) : void 
    {
        if (isset($aProperties['strName']) ) {
            $this->setName($aProperties['strName']); 
        }
        if (isset($aProperties['strIBAN']) ) {
            $this->setIBAN($aProperties['strIBAN']); 
        }
        if (isset($aProperties['strBIC']) ) {
            $this->setBIC($aProperties['strBIC']); 
        }
        if (isset($aProperties['strMandateId']) ) {
            $this->setMandateId($aProperties['strMandateId']); 
        }
        if (isset($aProperties['strDateOfSignature']) ) {
            $this->setDateOfSignature($aProperties['strDateOfSignature']); 
        }
        if (isset($aProperties['strDescription']) ) {
            $this->setDescription($aProperties['strDescription']); 
        }
        if (isset($aProperties['dblValue']) ) {
            $this->setValue(floatval($aProperties['dblValue'])); 
        }
        if (isset($aProperties['strUltimateName']) ) {
            $this->setUltimateName($aProperties['strUltimateName']); 
        }
        if (isset($aProperties['strPaymentId']) ) {
            $this->setPaymentId($aProperties['strPaymentId']); 
        }
    }
    
    /**
     * Set full name (lastname, firstname; company name; ...) 
     * @param string $strName
     */
    public function setName(string $strName) : void 
    {
        $this->strName = self::validString($strName, Sepa::MAX70);
    }
    
    /**
     * Set IBAN
     * @param string $strIBAN
     */
    public function setIBAN(string $strIBAN) : void
    {
        $this->strIBAN = $strIBAN;
    }
    
    /**
     * Set BIC
     * @param string $strBIC
     */
    public function setBIC(string $strBIC) : void
    {
        $this->strBIC = $strBIC;
    }
    
    /**
     * Set mandate identification (only debit) 
     * @param string $strMandateId
     */
    public function setMandateId(string $strMandateId) : void
    {
        $this->strMandateId = self::validString($strMandateId, Sepa::ID2);
    }
    
    /**
     * Set the date when the mandate identification signed (only debit)
     * @param mixed $DateOfSignature    may be string (format YYYY-MM-DD), int (unixtimestamp) or DateTime - object
     */
    public function setDateOfSignature($DateOfSignature) : void
    {
        if (is_object($DateOfSignature) && get_class($DateOfSignature) == 'DateTime') {
            // DateTime -object
            $this->strDateOfSignature = $DateOfSignature->format('Y-m-d');
        } else if (is_numeric($DateOfSignature)) {
            $this->strDateOfSignature = date('Y-m-d', $DateOfSignature);            
        } else {
            $this->strDateOfSignature = $DateOfSignature;           
        }
    }
    
    /**
     * Set ultimate debitor name (information purpose only) 
     * @param string $strUltimateName
     */
    public function setUltimateName(string $strUltimateName) : void
    {
        $this->strUltimateName = self::validString($strUltimateName, Sepa::MAX70);
    }
    
    /**
     * Set payment id 
     * @param string $strPaymentId
     */
    public function setPaymentId(string $strPaymentId) : void
    {
        $strPaymentId = self::validString($strPaymentId, Sepa::ID1);
        if (empty($strPaymentId)) {
            $strPaymentId = ($this->type == Sepa::CDD ? self::createUID() : 'NOTPROVIDED');
        }
        $this->strPaymentId = $strPaymentId;
    }
    
    /**
     * Set description
     * @param string $strDescription
     */
    public function setDescription(string $strDescription) : void
    {
        $this->strDescription = self::validString($strDescription, Sepa::MAX140);
    }
    
    /**
     * Set value of the transaction
     * @param float $dblValue
     */
    public function setValue(float $dblValue) : void 
    {
        $this->dblValue = $dblValue;
    }

    /**
     * Return type 
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Get full name (lastname, firstname; company name; ...)
     * @return string
     */
    public function getName() : string
    {
        return $this->strName;
    }
    
    /**
     * Get IBAN
     * @return string
     */
    public function getIBAN() : string
    {
        return $this->strIBAN;
    }
    
    /**
     * Get BIC
     * @return string
     */
    public function getBIC() : string
    {
        return $this->strBIC;
    }
    
    /**
     * Get mandate mdentification (only debit) 
     * @return string
     */
    public function getMandateId() : string
    {
        return $this->strMandateId;
    }
    
    /**
     * Return the date when the mandate identification signed (only debit)
     * @return string   (format YYYY-MM-DD)
     */
    public function getDateOfSignature() : string
    {
        return $this->strDateOfSignature;           
    }
    
    /**
     * Get ultimate debitor name (information purpose only) 
     * @return string
     */
    public function getUltimateName() : string
    {
        return $this->strUltimateName;
    }
    
    /**
     * Get payment id 
     * @return string
     */
    public function getPaymentId() : string
    {
        return $this->strPaymentId;
    }
    
    /**
     * Get description
     * @return string
     */
    public function getDescription() : string
    {
        return $this->strDescription;
    }
    
    /**
     * Get value of the transaction
     * @return float
     */
    public function getValue() : float
    {
        return $this->dblValue;
    }
    
    /**
     * Validate IBAN
     * @return int
     */
    private function validateIBAN() : int
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_NO_IBAN_VALIDATION)) {
            if (strlen($this->strIBAN) == 0) {
                $iErr |= Sepa::ERR_TX_IBAN_MISSING;
            } else if (Sepa::validateIBAN($this->strIBAN) != Sepa::OK) {
                $iErr |= Sepa::ERR_TX_INVALID_IBAN;
            }
        }
        return $iErr;
    }
    
    /**
     * Validate BIC
     * @return int
     */
    private function validateBIC() : int
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_NO_BIC_VALIDATION)) {
            if (strlen($this->strBIC) == 0) {
                $iErr |= Sepa::ERR_TX_BIC_MISSING;
            } else if (Sepa::validateBIC($this->strBIC) != Sepa::OK) {
                $iErr |= Sepa::ERR_TX_INVALID_BIC;
            }
        }
        return $iErr;
    }
    
    /**
     * Validate mandatory properties
     * @return int
     */
    private function validateMandatory() : int
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_IGNORE_MISSING_VALUE)) {
            if (strlen($this->strName) == 0) {
                $iErr |= Sepa::ERR_TX_NAME_MISSING;
            }
            if (strlen($this->strDescription) == 0) {
                $iErr |= Sepa::ERR_TX_DESCR_MISSING;
            }
            if ($this->dblValue <= 0.0) {
                $iErr |= Sepa::ERR_TX_ZERO_VALUE;
            }
            
            // additional check for debit
            if ($this->type == Sepa::CDD) {
                if (strlen($this->strMandateId) == 0) {
                    $iErr |= Sepa::ERR_TX_MAND_ID_MISSING;
                }
                if (strlen($this->strDateOfSignature) == 0) {
                    $iErr |= Sepa::ERR_TX_MAND_DOS_MISSING;
                } else if (!preg_match('/^([0-9]){4}-([0-9]){2}-([0-9]{2})/', $this->strDateOfSignature)) {
                    $iErr |= Sepa::ERR_TX_INVALID_MAND_DOS;
                }
            }
        }
        return $iErr;
    }
}