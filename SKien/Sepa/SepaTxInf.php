<?php
namespace SKien\Sepa;

/**
 * Class representing transaction (used for CreditTransferTx and DirectDebitTx)
 * 
 * uses helpers and const from trait SepaHelper
 * @see SepaHelper
 *
 * history:
 * date         version
 * 2020-02-18   initial version
 * 2020-05-21   added multi country validation
 *              renamed namespace to fit PSR-4 recommendations for autoloading
 *
 * @package SKien/Sepa
 * @version 1.1.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaTxInf
{
    use SepaHelper;
    
    /** type (Sepa::CDD or Sepa::CCT)
     *  @var string  */
    protected $type = '';
    /** full name (lastname, firstname; company name; ...)
     *  @var string  */
    protected $strName = '';
    /** IBAN
     *  @var string  */
    protected $strIBAN = '';
    /** BIC
     *  @var string  */
    protected $strBIC = '';
    /** mandate mdentification (only debit)
     *  @var string  */
    protected $strMandateId = '';
    /** date, mandate identification signed (only debit)
     *  @var string  */
    protected $strDateOfSignature = '';
    /** ultimate debitor name (information purpose only)
     *  @var string  */
    protected $strUltimateName = '';
    /** payment id
     *  @var string  */
    protected $strPaymentId = '';
    /** description
     *  @var string  */
    protected $strDescription = '';
    /** value of the transaction
     *  @var float   */
    protected $dblValue = '';
    
    /**
     * @param string $type (Sepa::CDD or Sepa::CCT)
     */
    public function __construct($type)
    {
        if (!$this->isValidType($type)) {
            return;
        }
        $this->type = $type;
    }
    
    /**
     * validate object
     * @return int
     */
    public function validate() 
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_NO_IBAN_VALIDATION)) {
            if (strlen($this->strIBAN) == 0) {
                $iErr |= Sepa::ERR_TX_IBAN_MISSING;
            } else if( Sepa::validateIBAN($this->strIBAN) != Sepa::OK) {
                $iErr |= Sepa::ERR_TX_INVALID_IBAN;
            }
        }
        if (!Sepa::checkValidation(Sepa::V_NO_BIC_VALIDATION)) {
            if (strlen($this->strBIC) == 0) {
                $iErr |= Sepa::ERR_TX_BIC_MISSING;
            } else if( Sepa::validateBIC($this->strBIC) != Sepa::OK) {
                $iErr |= Sepa::ERR_TX_INVALID_BIC;
            }
        }
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
        
        // create payment id if empty so far!
        if (empty($this->strPaymentId)) {
            $this->strPaymentId = ($this->type == Sepa::CDD ? self::createUID() : 'NOTPROVIDED');
        }
        
        return $iErr;
    }
    
    /**
     * get error message for error code
     * @param string $iError
     * @param string $strLF     Separator for multi errors (default: PHP_EOL; posible values: '<br />', '; ', ...) 
     * @return string
     */
    public function errorMsg($iError, $strLF = PHP_EOL) 
    {
        return Sepa::errorMsgTxInf($iError, $strLF);
    }
    
    /**
     *  set properties through associative array 
     * @param array $aProperties
     */
    public function fromArray(array $aProperties) 
    {
        if (isset( $aProperties['strName']) ) {
            $this->setName($aProperties['strName']); 
        }
        if (isset( $aProperties['strIBAN']) ) {
            $this->setIBAN($aProperties['strIBAN']); 
        }
        if (isset( $aProperties['strBIC']) ) {
            $this->setBIC($aProperties['strBIC']); 
        }
        if (isset( $aProperties['strMandateId']) ) {
            $this->setMandateId($aProperties['strMandateId']); 
        }
        if (isset( $aProperties['strDateOfSignature']) ) {
            $this->setDateOfSignature($aProperties['strDateOfSignature']); 
        }
        if (isset( $aProperties['strDescription']) ) {
            $this->setDescription($aProperties['strDescription']); 
        }
        if (isset( $aProperties['dblValue']) ) {
            $this->setValue($aProperties['dblValue']); 
        }
        if (isset( $aProperties['strUltimateName']) ) {
            $this->setUltimateName($aProperties['strUltimateName']); 
        }
        if (isset( $aProperties['strPaymentId']) ) {
            $this->setId($aProperties['strPaymentId']); 
        }
    }
    
    /**
     * set full name (lastname, firstname; company name; ...) 
     * @param string $strName
     */
    public function setName($strName) 
    {
        $this->strName = self::validString($strName, Sepa::MAX70);
    }
    
    /**
     * set IBAN
     * @param string $strIBAN
     */
    public function setIBAN($strIBAN) 
    {
        $this->strIBAN = $strIBAN;
    }
    
    /**
     * set BIC
     * @param string $strBIC
     */
    public function setBIC($strBIC) 
    {
        $this->strBIC = $strBIC;
    }
    
    /**
     * set mandate mdentification (only debit) 
     * @param string $strMandateId
     */
    public function setMandateId($strMandateId) 
    {
        $this->strMandateId = self::validString($strMandateId, Sepa::ID2);
    }
    
    /**
     * set date, mandate identification signed (only debit)
     * @param mixed $DateOfSignature    may be string (format YYYY-MM-DD), int (unixtimestamp) or DateTime - object
     */
    public function setDateOfSignature($DateOfSignature) 
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
     * set ultimate debitor name (information purpose only) 
     * @param string $strUltimateDebitor
     */
    public function setUltimateName($strUltimateName) 
    {
        $this->strUltimateName = self::validString($strUltimateName, Sepa::MAX70);
    }
    
    /**
     * set payment id 
     * @param string $strPaymentId
     */
    public function setPaymentId($strPaymentId) 
    {
        $strPaymentId = self::validString($strPaymentId, Sepa::ID1);
        if (empty($strPaymentId)) {
            $strPaymentId = ($this->type == Sepa::CDD ? self::createUID() : 'NOTPROVIDED');
        }
        $this->strPaymentId = $strPaymentId;
    }
    
    /**
     * set description
     * @param string $strDescription
     */
    public function setDescription($strDescription) 
    {
        $this->strDescription = self::validString($strDescription, Sepa::MAX140);
    }
    
    /**
     * set value of the transaction
     * @param float $dblValue
     */
    public function setValue($dblValue) 
    {
        $this->dblValue = $dblValue;
    }

    /**
     * get type 
     * @return string
     */
    public function getType() 
    {
        return $this->type;
    }

    /**
     * get full name (lastname, firstname; company name; ...)
     * @return string
     */
    public function getName() 
    {
        return $this->strName;
    }
    
    /**
     * get IBAN
     * @return string
     */
    public function getIBAN() 
    {
        return $this->strIBAN;
    }
    
    /**
     * get BIC
     * @return string
     */
    public function getBIC() 
    {
        return $this->strBIC;
    }
    
    /**
     * get mandate mdentification (only debit) 
     * @return string
     */
    public function getMandateId() 
    {
        return $this->strMandateId;
    }
    
    /**
     * get date, mandate identification signed (only debit)
     * @return string
     */
    public function getDateOfSignature() 
    {
        return $this->strDateOfSignature;           
    }
    
    /**
     * get ultimate debitor name (information purpose only) 
     * @return string
     */
    public function getUltimateName() 
    {
        return $this->strUltimateName;
    }
    
    /**
     * get payment id 
     * @return string
     */
    public function getPaymentId() 
    {
        return $this->strPaymentId;
    }
    
    /**
     * get description
     * @return string
     */
    public function getDescription() 
    {
        return $this->strDescription;
    }
    
    /**
     * get value of the transaction
     * @return float
     */
    public function getValue() 
    {
        return $this->dblValue;
    }
}