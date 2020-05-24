<?php
namespace SKien\Sepa;

/**
 * Main class representing Sepa-Document
 * 
 * uses helpers and const from trait SepaHelper
 * @see SepaHelper
 *
 * history:
 * date         version
 * 2020-02-18   initial version
 * 2020-05-21   renamed namespace to fit PSR-4 recommendations for autoloading
 *
 * @package SKien/Sepa
 * @version 1.1.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaDoc extends \DOMDocument
{
    use SepaHelper;
    
    /** unique id
     *  @var string  */
    protected $id = '';
    /** type of sepa document
     *  @var string  */
    protected $type = '';
    /** XML Base-Element
     *  @var DOMElement  */
    protected $xmlBase  = null; 
    /** overall count of transactions
     *  @var int     */
    protected $iTxCount = 0;
    /** DOM element containing overall count of transactions;
     *  @var DOMElement  */
    protected $xmlTxCount = null;
    /** controlsum (sum of all PII's)
     * @var float    */
    protected $dblCtrlSum = 0.0;
    /** DOM element containing controlsum
     *  @var DOMElement  */
    protected $xmlCtrlSum = null;
    /** count of invalid transactions
     *  @var int     */
    protected $iInvalidTxCount = 0;
    
    /**
     * creating SEPA document
     * @param string $type  type of transaction: Credit Transfer Transaction (SepaHelper::CCT) or Direct Debit Transaction (SepaHelper::CDD)
     */
    public function __construct($type) 
    {
        if (!$this->isValidType($type)) {
            return;
        }
        
        $aTypeInfo = array(
            Sepa::CCT   =>  array( 'pain' => '001.002.03', 'base' => 'CstmrCdtTrfInitn' ),  
            Sepa::CDD   =>  array( 'pain' => '008.002.02', 'base' => 'CstmrDrctDbtInitn' )
        );

        $strPain = $aTypeInfo[$type]['pain'];
        $strBase = $aTypeInfo[$type]['base'];
        
        parent::__construct("1.0", "UTF-8");
        
        $this->type = $type;
            
        $this->formatOutput = true;
        $this->preserveWhiteSpace = false;  // 'formatOutput' only works if 'preserveWhiteSpace' set to false
        
        $xmlRoot = $this->createElement("Document");
        $xmlRoot->setAttribute("xmlns",                 "urn:iso:std:iso:20022:tech:xsd:pain." . $strPain);
        $xmlRoot->setAttribute("xmlns:xsi",             "http://www.w3.org/2001/XMLSchema-instance");
        $xmlRoot->setAttribute("xsi:schemaLocation",    "urn:iso:std:iso:20022:tech:xsd:pain." . $strPain . " pain." . $strPain . ".xsd");
        $this->appendChild($xmlRoot);
        
        $this->xmlBase = $this->createElement($strBase);
        $xmlRoot->appendChild($this->xmlBase);
    }
    
    /**
     * creating group header and required elements
     * @param string $strName   name to use in header
     * @return string:          created unique id for document
     */
    public function createGroupHeader($strName) 
    {
        if ($this->xmlBase == null) {
            trigger_error('object not created successfull', E_USER_ERROR);
            return;
        }
        $xmlGrpHdr = $this->createElement("GrpHdr");
        $this->xmlBase->appendChild($xmlGrpHdr);
        
        $this->id = self::createUID();
        
        $this->addChild($xmlGrpHdr, 'MsgId', $this->id);
        $this->addChild($xmlGrpHdr, 'CreDtTm', date(DATE_ATOM));  // str_replace(' ', 'T', date('Y-m-d h:i:s'))); 
        $this->xmlTxCount = $this->addChild($xmlGrpHdr, 'NbOfTxs', 0);
        $this->xmlCtrlSum = $this->addChild($xmlGrpHdr, 'CtrlSum', sprintf("%01.2f", 0.0));
        
        $xmlNode = $this->addChild($xmlGrpHdr, 'InitgPty');
        $this->addChild($xmlNode, 'Nm', self::validString($strName, Sepa::MAX70));
        // SEPA spec recommends not to support 'InitgPty' -> 'Id'
        
        return $this->id;
    }

    /**
     * add payment instruction info (PII) to SEPAdocument.
     * 
     * PII is the base element to add transactions to SEPA document.
     * one SEPA document may contains multiple PII
     * 
     * @param SepaPmtInf $oPmtInf
     * @return int
     */
    public function addPaymentInstructionInfo(SepaPmtInf $oPmtInf) 
    {
        if ($this->xmlTxCount == null || $this->xmlCtrlSum == null) {
            trigger_error('call createGroupHeader() before add PII', E_USER_ERROR);
            return -1;
        }

        $iErr = $oPmtInf->validate();
        if ( $iErr == Sepa::OK) {
            $this->xmlBase->appendChild($oPmtInf);
            
            $this->addChild($oPmtInf, 'PmtInfId', $this->id);
            $this->addChild($oPmtInf, 'PmtMtd', $this->type);
            $oPmtInf->setTxCountNode($this->addChild($oPmtInf, 'NbOfTxs', 0));
            $oPmtInf->setCtrlSumNode($this->addChild($oPmtInf, 'CtrlSum', sprintf("%01.2f", 0.0)));
            
            // Payment Type Information
            $xmlPmtTpInf = $this->addChild($oPmtInf, 'PmtTpInf');
            $xmlNode = $this->addChild($xmlPmtTpInf, 'SvcLvl');
            $this->addChild($xmlNode, 'Cd', 'SEPA');
            
            if ($this->type == Sepa::CDD) {
                // only for directdebit
                $xmlNode = $this->addChild($xmlPmtTpInf, 'LclInstrm');
                $this->addChild($xmlNode, 'Cd', 'CORE');
                $this->addChild($xmlPmtTpInf, 'SeqTp', $oPmtInf->getSeqType());
                $this->addChild($oPmtInf, 'ReqdColltnDt', $oPmtInf->getCollectionDate());
                
                // Creditor Information
                $xmlNode = $this->addChild($oPmtInf, 'Cdtr');
                $this->addChild($xmlNode, 'Nm', $oPmtInf->getName());
    
                $xmlNode = $this->addChild($oPmtInf, 'CdtrAcct');
                $xmlNode = $this->addChild($xmlNode, 'Id');
                $this->addChild($xmlNode, 'IBAN', $oPmtInf->getIBAN());
    
                $xmlNode = $this->addChild($oPmtInf, 'CdtrAgt');
                $xmlNode = $this->addChild($xmlNode, 'FinInstnId');
                $this->addChild($xmlNode, 'BIC', $oPmtInf->getBIC());
                
                // Creditor Scheme Identification
                $xmlNode = $this->addChild($oPmtInf, 'CdtrSchmeId');
                $xmlNode = $this->addChild($xmlNode, 'Id');
                $xmlNode = $this->addChild($xmlNode, 'PrvtId');
                $xmlNode = $this->addChild($xmlNode, 'Othr');
                $this->addChild($xmlNode, 'Id', $oPmtInf->getCI());
                $xmlNode = $this->addChild($xmlNode, 'SchmeNm');
                $this->addChild($xmlNode, 'Prtry', 'SEPA');
            } else {
                // Requested Collection Date always 1999-01-01 for Credit Transfer 
                //   -> will be set to next possible date by executing Financial Institute
                $this->addChild($oPmtInf, 'ReqdColltnDt', date('1999-01-01'));
                
                // Creditor Information
                $xmlNode = $this->addChild($oPmtInf, 'Dbtr');
                $this->addChild($xmlNode, 'Nm', $oPmtInf->getName());
    
                $xmlNode = $this->addChild($oPmtInf, 'DbtrAcct');
                $xmlNode = $this->addChild($xmlNode, 'Id');
                $this->addChild($xmlNode, 'IBAN', $oPmtInf->getIBAN());
    
                $xmlNode = $this->addChild($oPmtInf, 'DbtrAgt');
                $xmlNode = $this->addChild($xmlNode, 'FinInstnId');
                $this->addChild($xmlNode, 'BIC', $oPmtInf->getBIC());
            }
        }       
        return $iErr;
    }
    
    /**
     * outputs generated SEPA document (XML - File)
     * 
     * @param string $strName       output filename
     * @param string $strTarget     target (default: 'attachment')
     */
    function output($strName, $strTarget='attachment') 
    {
        // send to browser
        header('Content-Type: application/xml');
        header('Content-Disposition: ' . $strTarget . '; filename="' . $strName . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
    
        echo $this->saveXML();
    }
    
    /**
     * calculate overall transactioncount and controlsum
     * @param float $dblValue
     */
    public function calc($dblValue) 
    {
        if (is_numeric($dblValue)) {
            $this->iTxCount++;
            $this->xmlTxCount->nodeValue = $this->iTxCount;
            $this->dblCtrlSum += $dblValue;
            $this->xmlCtrlSum->nodeValue = sprintf("%01.2f", $this->dblCtrlSum);
        }
    }
    
    /**
     * increments count of invalid transactions
     */
    public function incInvalidCount() 
    {
        $this->iInvalidTxCount++;
    }

    /**
     * create child element for given parent
     *
     * @param DOMElement    $xmlParent  parent for the node. If null, child of current instance is created
     * @param string        $strNode    nodename
     * @param string        $strValue   nodevalue. If empty, no value will be assigned (to create node only containing child elements)
     * @return DOMElement
     */
    protected function addChild($xmlParent, $strNode, $strValue='') 
    {
        $xmlNode = $this->createElement($strNode);
        if (!empty($strValue)) {
            $xmlNode->nodeValue = $strValue;
        }

        $xmlParent->appendChild($xmlNode);
        
        return $xmlNode;
    }
    
    /**
     * @return string
     */
    public function getId() 
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType() 
    {
        return $this->type;
    }

    /**
     * count of valid transactions
     * @return int
     */
    public function getTxCount() 
    {
        return $this->iTxCount;
    }

    /**
     * total value of valid transactions
     * @return float
     */
    public function getCtrlSum() 
    {
        return $this->dblCtrlSum;
    }
    
    /**
     * count of invalid transactions
     * @return int
     */
    public function getInvalidCount() 
    {
        return $this->iInvalidTxCount;
    }
}
