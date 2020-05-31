<?php
namespace SKien\Sepa;

/**
 * Class representing Payment Instruction Info (PII)
 * 
 * uses helpers and const from trait SepaHelper
 * @see SepaHelper
 *
 * ### History
 * ** 2020-02-18 **
 * - initial version.
 * 
 * ** 2020-05-21 **
 * - added multi country validation.
 * - renamed namespace to fit PSR-4 recommendations for autoloading.
 * 
 * @package SKien/Sepa
 * @since 1.0.0
 * @version 1.1.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaPmtInf extends \DOMElement
{
    use SepaHelper;
        
    /** unique id
     *  @var string  */
    protected $id = '';
    /** full name of applicant 
     *  @var string  */
    protected $strName = '';
    /** IBAN
     *  @var string  */
    protected $strIBAN = '';
    /** BIC
     *  @var string  */
    protected $strBIC = '';
    /** CI (Creditor Scheme Identification)
     *  @var string  */
    protected $strCI = '';
    /** sequence type (Sepa::FRST, Sepa::SEQ_RECURRENT, Sepa::SEQ_ONE_OFF, Sepa::SEQ_FINAL)
     *  @var string  */
    protected $strSeqType = '';
    /** parent document
     *  @var SepaDoc             */
    private $sepaDoc = null;
    /** count of transactions contained in PII
     *  @var int     */
    private $iTxCount = 0;
    /** DOM element containing count of transactions;
     *  @var DOMElement  */
    private $xmlTxCount = null;
    /** controlsum
     * @var float    */
    private $dblCtrlSum = 0.0;
    /** DOM element containing controlsum
     *  @var DOMElement  */
    private $xmlCtrlSum = null;
    
    /**
     * creating SEPA Payment Instruction Info (PII)
     * 
     * store parent doc and generate unique id 
     * @param SepaDoc $sepaDoc
     */
    function __construct(SepaDoc $sepaDoc) 
    {
        parent::__construct("PmtInf");
        // don't append any child at this point - created element have to be associated with a document after creation 
        
        $this->sepaDoc = $sepaDoc;
        $this->id = self::createUID();
    }

    /**
     * validate object
     * @return int errorcode (call errorMsg() to get coresponding text message)
     */
    public function validate() 
    {
        $iErr = Sepa::OK;
        if (!Sepa::checkValidation(Sepa::V_NO_IBAN_VALIDATION)) {
            if (strlen($this->strIBAN) == 0) {
                $iErr |= Sepa::ERR_PMT_IBAN_MISSING;
            } else if( Sepa::validateIBAN($this->strIBAN) != Sepa::OK) {
                $iErr |= Sepa::ERR_PMT_INVALID_IBAN;
            }
        }
        if (!Sepa::checkValidation(Sepa::V_NO_BIC_VALIDATION)) {
            if (strlen($this->strBIC) == 0) {
                $iErr |= Sepa::ERR_PMT_BIC_MISSING;
            } else if( Sepa::validateBIC($this->strBIC) != Sepa::OK) {
                $iErr |= Sepa::ERR_PMT_INVALID_BIC;
            }
        }
        if (!Sepa::checkValidation(Sepa::V_NO_CI_VALIDATION)) {
            if (strlen($this->strCI) == 0) {
                $iErr |= Sepa::ERR_PMT_CI_MISSING;
            } else if( Sepa::validateCI($this->strCI) != Sepa::OK) {
                $iErr |= Sepa::ERR_PMT_INVALID_CI;
            }
        }
        if (!Sepa::checkValidation(Sepa::V_IGNORE_MISSING_VALUE)) {
            if (strlen($this->strName) == 0) {
                $iErr |= Sepa::ERR_PMT_NAME_MISSING;
            }
            if (strlen($this->strSeqType) == 0) {
                $iErr |= Sepa::ERR_PMT_SEQ_TYPE_MISSING;
            } else if( $this->strSeqType != Sepa::SEQ_FIRST && $this->strSeqType != Sepa::SEQ_RECURRENT && $this->strSeqType != Sepa::SEQ_ONE_OFF && $this->strSeqType != Sepa::SEQ_FINAL) {
                $iErr |= Sepa::ERR_PMT_INVALID_SEQ_TYPE;
            }
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
        return Sepa::errorMsgPmtInf($iError, $strLF);
    }
    
    /**
     * create transaction
     * 
     * @param SepaTxInf $oTxInf
     * @return int
     */
    public function addTransaction(SepaTxInf $oTxInf) 
    {
        // transaction method have to fit to parent doc
        if ($this->sepaDoc->getType() != $oTxInf->getType()) {
            return Sepa::ERR_TX_INVALID_TYPE;
        }
        
        $iErr = $oTxInf->validate(); 
        if ( $iErr == Sepa::OK) {
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
                if ( strlen($strUltmtDbtr) > 0) {
                    $xmlNode = $this->addChild($xmlTx, 'UltmtDbtr');
                    $this->addChild($xmlNode, 'Nm', $strUltmtDbtr);
                }
            } else {
                $xmlTx = $this->addChild(null, 'CdtTrfTxInf');

                $xmlNode = $this->addChild($xmlTx, 'PmtId');
                $this->addChild($xmlNode, 'EndToEndId', $oTxInf->getPaymentId());
                
                // Instructed Amount
                $xmlNode = $this->addChild($xmlTx, 'InstdAmt', sprintf("%01.2f", $oTxInf->getValue()));
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
                if ( strlen($strUltmtDbtr) > 0) {
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
     * create child element for given parent
     * 
     * @param DOMElement    $xmlParent  parent for the node. If null, child of current instance is created
     * @param string        $strNode    nodename
     * @param string        $strValue   nodevalue. If empty, no value will be assigned (to create node only containing child elements)
     * @return DOMElement
     */
    protected function addChild($xmlParent, $strNode, $strValue = '') 
    {
        if($xmlParent == null) {
            $xmlParent = $this;
        }
            
        $xmlNode = $this->sepaDoc->createElement($strNode);
        if (!empty($strValue)) {
            $xmlNode->nodeValue = $strValue;
        }

        $xmlParent->appendChild($xmlNode);
        
        return $xmlNode;
    }

    /**
     * calculate transactioncount and controlsum for PII and update overall in parent doc
     * @param float $dblValue
     */
    public function calc($dblValue) 
    {
        if (is_numeric($dblValue)) {
            $this->iTxCount++;
            $this->xmlTxCount->nodeValue = $this->iTxCount;
            $this->dblCtrlSum += $dblValue;
            $this->xmlCtrlSum->nodeValue = sprintf("%01.2f", $this->dblCtrlSum);
            
            $this->sepaDoc->calc($dblValue);
        }
    }
    
    /**
     * Return the ID
     * @return string
     */
    public function getId() 
    {
        return $this->id;
    }
    
    /**
     * get collectiondate.
     * @return string
     */
    public function getCollectionDate() 
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
    public function setTxCountNode(\DOMElement $xmlNode) 
    {
        $this->xmlTxCount = $xmlNode;
    }
    
    /**
     * Set the xml node containing control sum.
     * @param \DOMElement $xmlNode
     */
    public function setCtrlSumNode(\DOMElement $xmlNode) 
    {
        $this->xmlCtrlSum = $xmlNode;
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
     * set CI (Creditor Scheme Identification)
     * @param string $strCI
     */
    public function setCI($strCI) 
    {
        $this->strCI = $strCI;
    }

    /**
     * set sequence type (Sepa::FRST, Sepa::SEQ_RECURRENT, Sepa::SEQ_ONE_OFF, Sepa::SEQ_FINAL)
     * @param string $strSeqType
     */
    public function setSeqType($strSeqType) 
    {
        $this->strSeqType = $strSeqType;
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
     * get CI (Creditor Scheme Identification)
     * @return string
     */
    public function getCI() 
    {
        return $this->strCI;
    }

    /**
     * get sequence type (Sepa::FRST, Sepa::SEQ_RECURRENT, Sepa::SEQ_ONE_OFF, Sepa::SEQ_FINAL)
     * @return string
     */
    public function getSeqType() 
    {
        return $this->strSeqType;
    }
}
