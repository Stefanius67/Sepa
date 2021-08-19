<?php
namespace SKien\Sepa;

/**
 * Class representing Payment Instruction Info (PII).
 *
 * #### Usage:
 * 1. Create a instance of this class and set all properties using the `SepaPmtInf::setXXX()` methods
 *    or `SepaPmtInf::fromArray()`.
 * 2. Attach the instance to the `SepaDoc` using the `SepaDoc::addPaymentInstructionInfo()` method.
 * 3. Generate the desired transactions (`new SepaTxInf()`) and assign them to this payment info instance.
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaPmtInf extends \DOMElement
{
    use SepaHelper;

    /** @var string  unique id*/
    protected string $id = '';
    /** @var string  full name of applicant*/
    protected string $strName = '';
    /** @var string  IBAN*/
    protected string $strIBAN = '';
    /** @var string  BIC*/
    protected string $strBIC = '';
    /** @var string  CI (Creditor Scheme Identification)*/
    protected string $strCI = '';
    /** @var string  sequence type (Sepa::FRST, Sepa::SEQ_RECURRENT, Sepa::SEQ_ONE_OFF, Sepa::SEQ_FINAL)*/
    protected string $strSeqType = '';
    /** @var SepaDoc parent document */
    private SepaDoc $sepaDoc;
    /** @var int count of transactions contained in PII */
    private int $iTxCount = 0;
    /** @var \DOMElement DOM element containing count of transactions */
    private ?\DOMElement $xmlTxCount = null;
    /** @var float controlsum */
    private float $dblCtrlSum = 0.0;
    /** @var \DOMElement DOM element containing controlsum */
    private ?\DOMElement $xmlCtrlSum = null;

    /**
     * Creating SEPA Payment Instruction Info (PII).
     * @param SepaDoc $sepaDoc
     */
    function __construct(SepaDoc $sepaDoc)
    {
        // store the parent doc and generate an unique id
        parent::__construct("PmtInf");

        // don't append any child at this point - created element have to be associated with a document after creation
        $this->sepaDoc = $sepaDoc;
        $this->id = self::createUID();
    }

    /**
     * Validate the object.
     * > This method usually dont have to be called from outside. It is
     * called, when an instance is attached to a SepaDoc!
     * @return int Sepa::OK or error code
     * @internal
     */
    public function validate() : int
    {
        $iErr = $this->validateIBAN();
        $iErr |= $this->validateBIC();
        $iErr |= $this->validateCI();
        $iErr |= $this->validateMandatory();
        return $iErr;
    }

    /**
     * Get the error message for given error code.
     * Since a payment info can contain multiple errors, the result may contain more than
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
        return Sepa::errorMsgPmtInf($iError, $strLF);
    }

    /**
     * Add a transaction.
     * <b>Important:</b><br/>
     * The PPI <b>must</b> be attached to the SepaDoc <b>before</b> the first transaktion is added!
     * @param SepaTxInf $oTxInf
     * @return int Sepa::OK or error code from SepaTxInf::validate()
     */
    public function addTransaction(SepaTxInf $oTxInf) : int
    {
        // element must been added as child to valid parent
        if ($this->xmlCtrlSum === null || $this->xmlTxCount === null) {
            trigger_error('element not added to parent (you must call SepaDoc::addPaymentInstructionInfo() before)!', E_USER_ERROR);
        }
        // transaction method have to fit to parent doc
        if ($this->sepaDoc->getType() != $oTxInf->getType()) {
            return Sepa::ERR_TX_INVALID_TYPE;
        }

        $iErr = $oTxInf->validate();
        if ($iErr == Sepa::OK) {
            if ($oTxInf->getType() == Sepa::CDD) {
                $xmlTx = $this->addChild(null, 'DrctDbtTxInf');

                $xmlNode = $this->addChild($xmlTx, 'PmtId');
                $this->addChild($xmlNode, 'EndToEndId', $oTxInf->getPaymentId());

                // Instructed Amount
                $xmlNode = $this->addChild($xmlTx, 'InstdAmt', sprintf("%01.2f", $oTxInf->getValue()));
                $xmlNode->SetAttribute('Ccy', 'EUR');

                // Mandate Related Information
                $xmlNode = $this->addChild($xmlTx, 'DrctDbtTx');
                $xmlNode = $this->addChild($xmlNode, 'MndtRltdInf');
                $this->addChild($xmlNode, 'MndtId', $oTxInf->getMandateId());
                $this->addChild($xmlNode, 'DtOfSgntr', $oTxInf->getDateOfSignature());
                $this->addChild($xmlNode, 'AmdmntInd', 'false');

                // Debitor Information  Name, IBAN, BIC
                $xmlNode = $this->addChild($xmlTx, 'DbtrAgt');
                $xmlNode = $this->addChild($xmlNode, 'FinInstnId');
                $this->addChild($xmlNode, 'BIC', $oTxInf->getBIC());

                $xmlNode = $this->addChild($xmlTx, 'Dbtr');
                $this->addChild($xmlNode, 'Nm', $oTxInf->getName());

                $xmlNode = $this->addChild($xmlTx, 'DbtrAcct');
                $xmlNode = $this->addChild($xmlNode, 'Id');
                $this->addChild($xmlNode, 'IBAN', $oTxInf->getIBAN());

                // Ultimate Debitor if requested
                $strUltmtDbtr = $oTxInf->getUltimateName();
                if (strlen($strUltmtDbtr) > 0) {
                    $xmlNode = $this->addChild($xmlTx, 'UltmtDbtr');
                    $this->addChild($xmlNode, 'Nm', $strUltmtDbtr);
                }
            } else {
                $xmlTx = $this->addChild(null, 'CdtTrfTxInf');

                $xmlNode = $this->addChild($xmlTx, 'PmtId');
                $this->addChild($xmlNode, 'EndToEndId', $oTxInf->getPaymentId());

                // Amount
                $xmlNode = $this->addChild($xmlTx, 'Amt');
                $xmlNode = $this->addChild($xmlNode, 'InstdAmt', sprintf("%01.2f", $oTxInf->getValue()));
                $xmlNode->SetAttribute('Ccy', 'EUR');

                // Creditor Information  Name, IBAN, BIC
                $xmlNode = $this->addChild($xmlTx, 'CdtrAgt');
                $xmlNode = $this->addChild($xmlNode, 'FinInstnId');
                $this->addChild($xmlNode, 'BIC', $oTxInf->getBIC());

                $xmlNode = $this->addChild($xmlTx, 'Cdtr');
                $this->addChild($xmlNode, 'Nm', $oTxInf->getName());

                $xmlNode = $this->addChild($xmlTx, 'CdtrAcct');
                $xmlNode = $this->addChild($xmlNode, 'Id');
                $this->addChild($xmlNode, 'IBAN', $oTxInf->getIBAN());

                // Ultimate Creditor if requested
                $strUltmtCbtr = $oTxInf->getUltimateName();
                if (strlen($strUltmtCbtr) > 0) {
                    $xmlNode = $this->addChild($xmlTx, 'UltmtCbtr');
                    $this->addChild($xmlNode, 'Nm', $strUltmtCbtr);
                }
            }

            // Remittance Information
            $xmlNode = $this->addChild($xmlTx, 'RmtInf');
            $this->addChild($xmlNode, 'Ustrd', $oTxInf->getDescription());

            // calculate count and controlsum of transactions
            $this->calc($oTxInf->getValue());
        } else {
            $this->sepaDoc->incInvalidCount();
        }
        return $iErr;
    }

    /**
     * Create a element and set it as child for given parent.
     * @param \DOMElement   $xmlParent  parent for the node. If null, child of current instance is created
     * @param string        $strNode    nodename
     * @param mixed         $value      nodevalue. If empty, no value will be assigned (to create node only containing child elements)
     * @return \DOMElement
     */
    protected function addChild(?\DOMElement $xmlParent, string $strNode, $value = '') : \DOMElement
    {
        if ($xmlParent == null) {
            $xmlParent = $this;
        }
        $xmlNode = $this->sepaDoc->createElement($strNode);
        if (!empty($value)) {
            $xmlNode->nodeValue = $value;
        }
        $xmlParent->appendChild($xmlNode);

        return $xmlNode;
    }

    /**
     * Calculate transaction count and controlsum for PII and update overall in parent doc.
     * @param float $dblValue
     */
    protected function calc(float $dblValue) : void
    {
        if ($this->xmlTxCount !== null && $this->xmlCtrlSum !== null) {
            $this->iTxCount++;
            $this->xmlTxCount->nodeValue = (string)$this->iTxCount;
            $this->dblCtrlSum += $dblValue;
            $this->xmlCtrlSum->nodeValue = sprintf("%01.2f", $this->dblCtrlSum);

            $this->sepaDoc->calc($dblValue);
        }
    }

    /**
     * Return the internal unique ID.
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Get the collection date.
     * @return string
     */
    public function getCollectionDate() : string
    {
        // Requested Collection Date depends on sequence type
        $this->strSeqType == Sepa::SEQ_RECURRENT ? $iDays = 3 : $iDays = 6;
        $dtCollect = self::calcCollectionDate($iDays);
        return date('Y-m-d', $dtCollect);
    }

    /**
     * Set the xml node containing transactions count.
     * @param \DOMElement $xmlNode
     */
    public function setTxCountNode(\DOMElement $xmlNode) : void
    {
        $this->xmlTxCount = $xmlNode;
    }

    /**
     * Set the xml node containing control sum.
     * @param \DOMElement $xmlNode
     */
    public function setCtrlSumNode(\DOMElement $xmlNode) : void
    {
        $this->xmlCtrlSum = $xmlNode;
    }

    /**
     * Set properties through associative array.
     * Example array:
     * ```php
     *   $aPPI = [
     *       'strName' => '<name>',
     *       'strCI' => '<CI>',
     *       'strIBAN' => '<IBAN>',
     *       'strBIC' => '<BIC>',
     *       'strSeqType' => Sepa::SEQ_xxx,
     *   ];
     * ```
     * The array does not have to contain all of the properties. The missing properties
     * can be set later using the respective `setXXX()` method.
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
            'strCI' => 'setCI',
            'strSeqType' => 'setSeqType',
        ];
        foreach ($aPropertyMap as $strKey => $strFunc) {
            if (isset($aProperties[$strKey])) {
                $this->$strFunc($aProperties[$strKey]);
            }
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
     * Set IBAN.
     * @param string $strIBAN
     */
    public function setIBAN(string $strIBAN) : void
    {
        $this->strIBAN = $strIBAN;
    }

    /**
     * Set BIC.
     * @param string $strBIC
     */
    public function setBIC(string $strBIC) : void
    {
        $this->strBIC = $strBIC;
    }

    /**
     * Sset CI (Creditor Scheme Identification).
     * @param string $strCI
     */
    public function setCI(string $strCI) : void
    {
        $this->strCI = $strCI;
    }

    /**
     * Set sequence type (Sepa::FRST, Sepa::SEQ_RECURRENT, Sepa::SEQ_ONE_OFF, Sepa::SEQ_FINAL).
     * @param string $strSeqType
     */
    public function setSeqType(string $strSeqType) : void
    {
        $this->strSeqType = $strSeqType;
    }

    /**
     * Get full name (lastname, firstname; company name; ...).
     * @return string
     */
    public function getName() : string
    {
        return $this->strName;
    }

    /**
     * Get IBAN.
     * @return string
     */
    public function getIBAN() : string
    {
        return $this->strIBAN;
    }

    /**
     * Get BIC.
     * @return string
     */
    public function getBIC() : string
    {
        return $this->strBIC;
    }

    /**
     * Get CI (Creditor Scheme Identification).
     * @return string
     */
    public function getCI() : string
    {
        return $this->strCI;
    }

    /**
     * Get sequence type (Sepa::FRST, Sepa::SEQ_RECURRENT, Sepa::SEQ_ONE_OFF, Sepa::SEQ_FINAL).
     * @return string
     */
    public function getSeqType() : string
    {
        return $this->strSeqType;
    }

    /**
     * Validate the IBAN.
     * @return int Sepa::OK or errorcode
     */
    private function validateIBAN() : int
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_NO_IBAN_VALIDATION)) {
            if (strlen($this->strIBAN) == 0) {
                $iErr = Sepa::ERR_PMT_IBAN_MISSING;
            } else if (Sepa::validateIBAN($this->strIBAN) != Sepa::OK) {
                $iErr = Sepa::ERR_PMT_INVALID_IBAN;
            }
        }
        return $iErr;
    }

    /**
     * Validate the BIC.
     * @return int Sepa::OK or errorcode
     */
    private function validateBIC() : int
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_NO_BIC_VALIDATION)) {
            if (strlen($this->strBIC) == 0) {
                $iErr = Sepa::ERR_PMT_BIC_MISSING;
            } else if (Sepa::validateBIC($this->strBIC) != Sepa::OK) {
                $iErr = Sepa::ERR_PMT_INVALID_BIC;
            }
        }
        return $iErr;
    }

    /**
     * Validate the CI.
     * @return int Sepa::OK or errorcode
     */
    private function validateCI() : int
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_NO_CI_VALIDATION)) {
            if (strlen($this->strCI) == 0) {
                $iErr = Sepa::ERR_PMT_CI_MISSING;
            } else if (Sepa::validateCI($this->strCI) != Sepa::OK) {
                $iErr = Sepa::ERR_PMT_INVALID_CI;
            }
        }
        return $iErr;
    }

    /**
     * Validate mandatory fields.
     * @return int Sepa::OK or errorcode
     */
    private function validateMandatory() : int
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_IGNORE_MISSING_VALUE)) {
            if (strlen($this->strName) == 0) {
                $iErr |= Sepa::ERR_PMT_NAME_MISSING;
            }
            if (strlen($this->strSeqType) == 0) {
                $iErr |= Sepa::ERR_PMT_SEQ_TYPE_MISSING;
            } else if (!$this->isValidateSeqType($this->strSeqType)) {
                $iErr |= Sepa::ERR_PMT_INVALID_SEQ_TYPE;
            }
        }
        return $iErr;
    }

    /**
     * Check for valid sequence type.
     * @param string $strSeqType type to check
     * @return bool
     */
    private function isValidateSeqType(string $strSeqType) : bool
    {
        $aValid = [
            Sepa::SEQ_FIRST,
            Sepa::SEQ_RECURRENT,
            Sepa::SEQ_ONE_OFF,
            Sepa::SEQ_FINAL
        ];
        return in_array($strSeqType, $aValid);
    }
}
