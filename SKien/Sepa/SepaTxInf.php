<?php
namespace SKien\Sepa;

/**
 * Class representing a transaction.
 *
 * The class can be used for both transaction types - Sepa::CDD or Sepa::CCT.
 *
 * Mandatory properties:
 * - Name
 * - IBAN
 * - BIC
 * - ammount
 * - Description
 *
 * only mandatory for CDD:
 * - Mandate ID
 * - Mandate date of signature
 * - If no payment ID is set, an unique ID is created internaly
 *
 * > <b>Note:</b><br/>
 * > The return values of the `getXXX()` methods can differ from the passed values
 * (`setXXX() / fromArray()`) since they  may have been converted too allowed charecters and
 * limited to a max. length.
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
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
    /** @var string purpose */
    protected string $strPurpose = '';

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
     * Validate the object.
     * > This method usually dont have to be called from outside. It is
     * called, when an instance is added to a PPI!
     * @return int Sepa::OK or error code
     * @internal
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
     * Get the error message for given error code.
     * Since a transaction info can contain multiple errors, the result may contain more than
     * one message separated by a separator. <br/>
     * The separator can be specified to meet the needs of different output destinations.
     * Default value is a linefeed.
     * @param int $iError   the errorcode
     * @param string $strLF     Separator for multiple errors (default: PHP_EOL; posible values: '&lt;br/&gt;', ';', ...)
     * @return string
     */
    public function errorMsg(int $iError, string $strLF = PHP_EOL) : string
    {
        // route to the Sepa class to get localized message
        return Sepa::errorMsgTxInf($iError, $strLF);
    }

    /**
     * Set properties through associative array.
     * Example array:
     * ```php
     *   $aPPI = [
     *       'strName' => '<name>',
     *       'strIBAN' => '<IBAN>',
     *       'strBIC' => '<BIC>',
     *       'strMandateId' => '<MandateId>',
     *       'strDateOfSignature' => '<DateOfSignature>',
     *       'strDescription' => '<Description>',
     *       'strUltimateName' => '<UltimateName>',
     *       'strPaymentId' => '<PaymentId>',
     *       'strPurpose' => 'setPurpose',
     *       'dblValue' => 123.4,
     *   ];
     * ```
     * The array does not have to contain all of the properties. Mandatory properties
     * can be set later using the respective `setXXX()` method, optional ones can be left out.
     *
     * @param array<string> $aProperties    see description
     */
    public function fromArray(array $aProperties) : void
    {
        // use the setter methods to ensure that all validations are made!
        $aPropertyMap = [
            'strName' => 'setName',
            'strIBAN' => 'setIBAN',
            'strBIC' => 'setBIC',
            'strMandateId' => 'setMandateId',
            'strDateOfSignature' => 'setDateOfSignature',
            'strDescription' => 'setDescription',
            'strUltimateName' => 'setUltimateName',
            'strPaymentId' => 'setPaymentId',
            'strPurpose' => 'setPurpose',
        ];
        foreach ($aPropertyMap as $strKey => $strFunc) {
            if (isset($aProperties[$strKey])) {
                $this->$strFunc($aProperties[$strKey]);
            }
        }
        if (isset($aProperties['dblValue'])) {
            $this->setValue(floatval($aProperties['dblValue']));
        }
    }

    /**
     * Set full name (lastname, firstname; company name; ...).
     * @param string $strName
     */
    public function setName(string $strName) : void
    {
        $this->strName = self::validString($strName, Sepa::MAX70);
    }

    /**
     * Set the IBAN.
     * @param string $strIBAN
     */
    public function setIBAN(string $strIBAN) : void
    {
        $this->strIBAN = $strIBAN;
    }

    /**
     * Set the BIC.
     * @param string $strBIC
     */
    public function setBIC(string $strBIC) : void
    {
        $this->strBIC = $strBIC;
    }

    /**
     * Set the mandate identification (only CDD).
     * @param string $strMandateId
     */
    public function setMandateId(string $strMandateId) : void
    {
        $this->strMandateId = self::validString($strMandateId, Sepa::ID2);
    }

    /**
     * Set the date when the mandate identification signed (only CDD).
     * @param \DateTime|int|string $DateOfSignature    may be string (format YYYY-MM-DD), int (unixtimestamp) or DateTime - object
     */
    public function setDateOfSignature($DateOfSignature) : void
    {
        if (is_object($DateOfSignature) && get_class($DateOfSignature) == 'DateTime') {
            // DateTime -object
            $this->strDateOfSignature = $DateOfSignature->format('Y-m-d');
        } else if (is_numeric($DateOfSignature)) {
            $this->strDateOfSignature = date('Y-m-d', intval($DateOfSignature));
        } else {
            $this->strDateOfSignature = (string)$DateOfSignature;
        }
    }

    /**
     * Set ultimate debitor name (optional - information purpose only)
     * @param string $strUltimateName
     */
    public function setUltimateName(string $strUltimateName) : void
    {
        $this->strUltimateName = self::validString($strUltimateName, Sepa::MAX70);
    }

    /**
     * Set the payment id (only CDD).
     * If no payment ID set, an unique ID is created internaly.
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
     * Set the description.
     * @param string $strDescription
     */
    public function setDescription(string $strDescription) : void
    {
        $this->strDescription = self::validString($strDescription, Sepa::MAX140);
    }

    /**
     * Set the purpose.
     * The purpose is an optional value!
     * If set, only ISO 20022 codes of the ExternalPurpose1Code list are allowed.
     * Referr to the actual list that is available in worksheet '11-Purpose 'of the Excel
     * file provided in the download at
     * [www.iso20022.org](https://www.iso20022.org/catalogue-messages/additional-content-messages/external-code-sets)
     * > <b>Attention:</b><br/>
     * > There is no validation whether in this module nor through the provided XSD schemas for
     * this value. To avoid rejection of your data, you have to take care for valid values on your own.
     * @link ./Transaction-Purpose-Codes
     * @param string $strPurpose
     */
    public function setPurpose(string $strPurpose) : void
    {
        $this->strPurpose = strtoupper(substr($strPurpose, 0, 4));
    }

    /**
     * Set the value (amount) of the transaction.
     * @param float $dblValue
     */
    public function setValue(float $dblValue) : void
    {
        $this->dblValue = $dblValue;
    }

    /**
     * Return the transaction type.
     * @return string (Sepa::CDD or Sepa::CCT)
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Get the full name (lastname, firstname; company name; ...).
     * @return string
     */
    public function getName() : string
    {
        return $this->strName;
    }

    /**
     * Get the IBAN.
     * @return string
     */
    public function getIBAN() : string
    {
        return $this->strIBAN;
    }

    /**
     * Get the BIC.
     * @return string
     */
    public function getBIC() : string
    {
        return $this->strBIC;
    }

    /**
     * Get the mandate identification.
     * @return string
     */
    public function getMandateId() : string
    {
        return $this->strMandateId;
    }

    /**
     * Return the date when the mandate identification signed.
     * @return string   (format YYYY-MM-DD)
     */
    public function getDateOfSignature() : string
    {
        return $this->strDateOfSignature;
    }

    /**
     * Get the ultimate debitor name.
     * @return string
     */
    public function getUltimateName() : string
    {
        return $this->strUltimateName;
    }

    /**
     * Get the payment id.
     * This ID can be created internaly.
     * @return string
     */
    public function getPaymentId() : string
    {
        return $this->strPaymentId;
    }

    /**
     * Get the description.
     * @return string
     */
    public function getDescription() : string
    {
        return $this->strDescription;
    }

    /**
     * Get the purpose.
     * @return string
     */
    public function getPurpose() : string
    {
        return $this->strPurpose;
    }

    /**
     * Get the value of the transaction.
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