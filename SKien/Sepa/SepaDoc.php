<?php
namespace SKien\Sepa;

/**
 * Main class to create a Sepa-Document.
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaDoc extends \DOMDocument
{
    use SepaHelper;

    /** @var string  unique id  */
    protected string $id = '';
    /** @var string type of sepa document  */
    protected string $type = '';
    /** @var string sepa version to use  */
    protected string $strSepaVersion = '';
    /** @var \DOMElement XML Base-Element       */
    protected ?\DOMElement $xmlBase = null;
    /** @var int overall count of transactions  */
    protected int $iTxCount = 0;
    /** @var \DOMElement DOM element containing overall count of transactions   */
    protected ?\DOMElement $xmlTxCount = null;
    /** @var float controlsum (sum of all PII's) */
    protected float $dblCtrlSum = 0.0;
    /** @var \DOMElement DOM element containing controlsum      */
    protected ?\DOMElement $xmlCtrlSum = null;
    /** @var int count of invalid transactions*/
    protected int $iInvalidTxCount = 0;

    /**
     * Creating a SEPA document.
     * A single SEPA document can only hold one type of transactions: <ul>
     * <li> Credit Transfer Transaction (Sepa::CCT) </li>
     * <li> Direct Debit Transaction (Sepa::CDD) </li></ul>
     * The Sepa version in which the file is to be created depends primarily on the
     * requirements of the bank to which the created file is to be submitted. It is
     * recommended to use the latest version supported by the institute.
     * @param string $type  type of transaction
     * @param string $strSepaVersion    Sepa version to use (Sepa::V26, Sepa::V29, Sepa::V30)
     */
    public function __construct(string $type, string $strSepaVersion = Sepa::V30)
    {
        // invalid type causes E_USER_ERROR
        $this->isValidType($type);
        $aTypeBase = [Sepa::CCT => 'CstmrCdtTrfInitn', Sepa::CDD => 'CstmrDrctDbtInitn'];

        $strPain = Sepa::getPainVersion($type, $strSepaVersion);
        $strBase = $aTypeBase[$type];

        parent::__construct("1.0", "UTF-8");

        $this->type = $type;
        $this->strSepaVersion = $strSepaVersion;

        $this->formatOutput = true;
        $this->preserveWhiteSpace = false; // 'formatOutput' only works if 'preserveWhiteSpace' set to false

        $xmlRoot = $this->createElement("Document");
        $xmlRoot->setAttribute("xmlns", "urn:iso:std:iso:20022:tech:xsd:" . $strPain);
        $xmlRoot->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $xmlRoot->setAttribute("xsi:schemaLocation", "urn:iso:std:iso:20022:tech:xsd:" . $strPain . " " . $strPain . ".xsd");
        $this->appendChild($xmlRoot);

        $this->xmlBase = $this->createElement($strBase);
        $xmlRoot->appendChild($this->xmlBase);
    }

    /**
     * Creating group header and required elements.
     * If no `id` i spassed, a unique identifier is internaly generated. This id
     * can be used to assign it to the data elements that were used to generate the
     * transactions contained in this file.
     * This ID is used by the receiving bank institute to avoid double processing.
     * @param string $strName   name (initiator of the transactions)
     * @param string $id        unique id for the document (if null, id will be generated)
     * @return string           the (possibly created) id for the document
     */
    public function createGroupHeader(string $strName, string $id = null) : string
    {
        if ($this->xmlBase != null) {
            $xmlGrpHdr = $this->createElement("GrpHdr");
            $this->xmlBase->appendChild($xmlGrpHdr);

            $this->id = $id ?? self::createUID();

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
     * Add payment instruction info (PII) to the document.
     * PII is the base element to add transactions to the SEPA document.
     * > One SEPA document may contains multiple PII.
     * @see SepaPmtInf
     * @param SepaPmtInf $oPmtInf
     * @return int Sepa::OK or error code from SepaPmtInf::validate()
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

                if (($strCategoryPurpose = $oPmtInf->getCategoryPurpose()) != '') {
                    $xmlNode = $this->addChild($xmlPmtTpInf, 'CtgyPurp');
                    $this->addChild($xmlNode, 'Cd', $strCategoryPurpose);
                }

                if ($this->type == Sepa::CDD) {
                    // only for directdebit
                    $xmlNode = $this->addChild($xmlPmtTpInf, 'LclInstrm');
                    $this->addChild($xmlNode, 'Cd', 'CORE');
                    $this->addChild($xmlPmtTpInf, 'SeqTp', $oPmtInf->getSeqType());
                    $this->addChild($oPmtInf, 'ReqdColltnDt', $oPmtInf->getCollExecDate(Sepa::CDD));

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
                    $this->addChild($oPmtInf, 'ReqdExctnDt', $oPmtInf->getCollExecDate(Sepa::CCT));

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
     * Outputs the generated SEPA document as XML-File through the browser.
     * > - To save the XML file direct anywhere on the server, use the `DOMDocument::save()` method.<br/>
     * > - To save the XML file in a database use the `DOMDocument::saveXML()` method.<br/>
     *
     * The target should always be 'attachment' (<i>indicating it should be downloaded; most browsers
     * presenting a 'Save as' dialog, prefilled with the value of the filename parameter</i>). <br/>
     * For test purposes you can change it to 'inline' to display the XML inside of the browser
     * rather than save it to a file.
     * @param string $strName       output filename
     * @param string $strTarget     target (default: 'attachment')
     */
    public function output(string $strName, string $strTarget = 'attachment') : void
    {
        // Set the HTTP header and echo the generated content
        header('Content-Type: application/xml');
        header('Content-Disposition: ' . $strTarget . '; filename="' . $strName . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $this->saveXML();
    }

    /**
     * Calculate overall transaction count and controlsum.
     * This method should only be called internal by the `SepaPmtInf`class!
     * @param float $dblValue
     * @internal
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
     * Increments the count of invalid transactions.
     * @internal
     */
    public function incInvalidCount() : void
    {
        $this->iInvalidTxCount++;
    }

    /**
     * Create child element for given parent.
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
     * Return the internal generated ID.
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
     * Count of valid transactions.
     * @return int
     */
    public function getTxCount() : int
    {
        return $this->iTxCount;
    }

    /**
     * Total value of valid transactions.
     * @return float
     */
    public function getCtrlSum() : float
    {
        return $this->dblCtrlSum;
    }

    /**
     * Get the count of invalid transactions passed.
     * @return int
     */
    public function getInvalidCount() : int
    {
        return $this->iInvalidTxCount;
    }
}
