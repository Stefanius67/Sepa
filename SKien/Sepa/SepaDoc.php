<?php
namespace SKien\Sepa;

/**
 * Main class representing Sepa-Document
 *
 * uses helpers and const from trait SepaHelper
 * @see SepaHelper
 *
 * #### History:
 * - *2020-02-18*   initial version.
 * - *2020-05-21*   renamed namespace to fit PSR-4 recommendations for autoloading.
 * - *2020-07-22*   added missing PHP 7.4 type hints / docBlock changes
 *
 * @package SKien/Sepa
 * @since 1.0.0
 * @version 1.2.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaDoc extends \DOMDocument
{
    use SepaHelper;

    /** @var string  unique id  */
    protected string $id = '';
    /** @var string  type of sepa document  */
    protected string $type = '';
    /** @var \DOMElement  XML Base-Element       */
    protected ?\DOMElement $xmlBase = null;
    /** @var int     overall count of transactions  */
    protected int $iTxCount = 0;
    /** @var \DOMElement  DOM element containing overall count of transactions   */
    protected ?\DOMElement $xmlTxCount = null;
    /** @var float    controlsum (sum of all PII's) */
    protected float $dblCtrlSum = 0.0;
    /** @var \DOMElement  DOM element containing controlsum      */
    protected ?\DOMElement $xmlCtrlSum = null;
    /** @var int     count of invalid transactions*/
    protected int $iInvalidTxCount = 0;

    /**
     * creating SEPA document
     * @param string $type  type of transaction: Credit Transfer Transaction (SepaHelper::CCT) or Direct Debit Transaction (SepaHelper::CDD)
     */
    public function __construct(string $type)
    {
        // invalid type causes E_USER_ERROR
        $this->isValidType($type);
        $aTypeInfo = array(
            Sepa::CCT => array('pain' => '001.002.03', 'base' => 'CstmrCdtTrfInitn'),
            Sepa::CDD => array('pain' => '008.002.02', 'base' => 'CstmrDrctDbtInitn')
        );

        $strPain = $aTypeInfo[$type]['pain'];
        $strBase = $aTypeInfo[$type]['base'];

        parent::__construct("1.0", "UTF-8");

        $this->type = $type;

        $this->formatOutput = true;
        $this->preserveWhiteSpace = false; // 'formatOutput' only works if 'preserveWhiteSpace' set to false

        $xmlRoot = $this->createElement("Document");
        $xmlRoot->setAttribute("xmlns", "urn:iso:std:iso:20022:tech:xsd:pain." . $strPain);
        $xmlRoot->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $xmlRoot->setAttribute("xsi:schemaLocation", "urn:iso:std:iso:20022:tech:xsd:pain." . $strPain . " pain." . $strPain . ".xsd");
        $this->appendChild($xmlRoot);

        $this->xmlBase = $this->createElement($strBase);
        $xmlRoot->appendChild($this->xmlBase);
    }

    /**
     * creating group header and required elements
     * @param string $strName   name to use in header
     * @return string          created unique id for document
     */
    public function createGroupHeader(string $strName) : string
    {
        if ($this->xmlBase != null) {
            $xmlGrpHdr = $this->createElement("GrpHdr");
            $this->xmlBase->appendChild($xmlGrpHdr);

            $this->id = self::createUID();

            $this->addChild($xmlGrpHdr, 'MsgId', $this->id);
            $this->addChild($xmlGrpHdr, 'CreDtTm', date(DATE_ATOM)); // str_replace(' ', 'T', date('Y-m-d h:i:s')));
            $this->xmlTxCount = $this->addChild($xmlGrpHdr, 'NbOfTxs', 0);
            $this->xmlCtrlSum = $this->addChild($xmlGrpHdr, 'CtrlSum', sprintf("%01.2f", 0.0));

            $xmlNode = $this->addChild($xmlGrpHdr, 'InitgPty');
            $this->addChild($xmlNode, 'Nm', self::validString($strName, Sepa::MAX70));
            // SEPA spec recommends not to support 'InitgPty' -> 'Id'
        }

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
    public function addPaymentInstructionInfo(SepaPmtInf $oPmtInf) : int
    {
        $iErr = -1;
        if ($this->xmlBase === null || $this->xmlTxCount === null || $this->xmlCtrlSum === null) {
            trigger_error('call createGroupHeader() before add PII', E_USER_ERROR);
        } else {
            $iErr = $oPmtInf->validate();
            if ($iErr == Sepa::OK) {
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
        }
        return $iErr;
    }

    /**
     * Outputs generated SEPA document (XML - File).
     * Set the HTTP header and echo the generated content.
     *
     * @param string $strName       output filename
     * @param string $strTarget     target (default: 'attachment')
     */
    public function output(string $strName, string $strTarget = 'attachment') : void
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
    public function calc(float $dblValue) : void
    {
        if ($this->xmlTxCount === null || $this->xmlCtrlSum === null) {
            trigger_error('call createGroupHeader() before calc()', E_USER_ERROR);
        } else {
            $this->iTxCount++;
            $this->xmlTxCount->nodeValue = (string)$this->iTxCount;
            $this->dblCtrlSum += $dblValue;
            $this->xmlCtrlSum->nodeValue = sprintf("%01.2f", $this->dblCtrlSum);
        }
    }

    /**
     * increments count of invalid transactions
     */
    public function incInvalidCount() : void
    {
        $this->iInvalidTxCount++;
    }

    /**
     * create child element for given parent
     *
     * @param \DOMElement   $xmlParent  parent for the node. If null, child of current instance is created
     * @param string        $strNode    nodename
     * @param mixed         $value      nodevalue. If empty, no value will be assigned (to create node only containing child elements)
     * @return \DOMElement
     */
    protected function addChild(\DOMElement $xmlParent, string $strNode, $value = '') : \DOMElement
    {
        $xmlNode = $this->createElement($strNode);
        if (!empty($value)) {
            $xmlNode->nodeValue = $value;
        }
        $xmlParent->appendChild($xmlNode);

        return $xmlNode;
    }

    /**
     * Return the ID (may be internal generated).
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Return the type.
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Count of valid transactions
     * @return int
     */
    public function getTxCount() : int
    {
        return $this->iTxCount;
    }

    /**
     * Total value of valid transactions
     * @return float
     */
    public function getCtrlSum() : float
    {
        return $this->dblCtrlSum;
    }

    /**
     * count of invalid transactions
     * @return int
     */
    public function getInvalidCount() : int
    {
        return $this->iInvalidTxCount;
    }
}
