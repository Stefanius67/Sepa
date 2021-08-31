<?php
declare(strict_types=1);

namespace SKien\Sepa;

/**
 * This class represents the SCL directory provided by the 'Deutsche Bundesbank'.
 *
 * The SCL directory contains all business identifier codes (BICs) that can be reached
 * within the SEPA payment area.
 * (https://www.bundesbank.de/en/tasks/payment-systems/rps/sepa-clearer/scl-directory/scl-directory-626672)
 *
 * With this class, the data made available as a CSV file can be loaded from the
 * Internet and saved in a XML file on the own server. The XML file contains
 * a timestamp of the latest download that can be retrieved - so its on the user to
 * decide, in which timespan he will update the informations. Optionally, this update
 * process can be automated by specifying a time period.
 *
 * The clas contains functionality to:
 * - check, if a given BIC is valid
 * - get the service provider name (bankname) to a given BIC
 * - get a list of the payment service providers (all or filtered by country)
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SclDirectory
{
    /** the url where the scl directory can be accessed */
    protected const DATA_PROVIDER_URL = 'https://www.bundesbank.de/scl-directory';
    /** the data file */
    protected const DATAFILE = 'scl-directory.xml';

    /** @var string path to the data file     */
    protected string $strDataPath = '';
    /** @var \DOMDocument      */
    protected ?\DOMDocument $oDoc = null;
    /** @var string last error                   */
    protected string $strLastError;
    /** @var integer unix timestamp of last data update     */
    protected int $uxtsLastUpdated = 0;

    /**
     * Create SCL directory.
     * @param string $strDataPath path to the XML data file
     */
    public function __construct(string $strDataPath = '')
    {
        $this->strDataPath = rtrim($strDataPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Init the object.
     * If the XML data file already exist, it is checked if writable.
     * If not, the datapath is checked if writable
     * @return bool false, if any error occur
     */
    public function init() : bool
    {
        $this->strLastError = '';
        $strXMLName = self::DATAFILE;
        if (strlen($this->strDataPath) > 0) {
            $strXMLName = $this->strDataPath . DIRECTORY_SEPARATOR . self::DATAFILE;
        }

        if (file_exists($strXMLName)) {
            if (!is_writable($strXMLName)) {
                $this->strLastError .= 'readonly data file ' . $strXMLName . '!';
            } else {
                $this->oDoc = new \DOMDocument();
                $this->oDoc->load($strXMLName);
            }
        } else {
            $strPath = realpath($this->strDataPath);
            if ($strPath === false || !is_writable($strPath)) {
                $this->strLastError .= ' (no rights to write on directory ' . $strPath . ')';
            }
        }
        return (strlen($this->strLastError) == 0);
    }

    /**
     * Get the date the data has been updated last.
     * @return int date as unix timestamp
     */
    public function lastUpdated() : int
    {
        $uxtsLastUpdated = 0;
        if ($this->oDoc !== null && $this->oDoc->documentElement !== null) {
            $strDate = $this->oDoc->documentElement->getAttribute('created');
            if (strlen($strDate) > 0) {
                $uxtsLastUpdated = strtotime($strDate);
            }
        }
        return intval($uxtsLastUpdated);
    }

    /**
     * Check, if the requested BIC exists.
     * @param string $strBIC
     * @return bool
     */
    public function isValidBIC(string $strBIC) : bool
    {
        return ($this->getProviderNode($strBIC) !== null);
    }

    /**
     * Get the name of the provider to the given BIC.
     * Since the provider names in the supported directory all  in uppercase, this
     * can be converted to upper case words by setting the `$bToUCWords`parameter to true.
     * @param string $strBIC BIC to get the name for
     * @param bool $bToUCWords convert the provider names to Uppercase Words
     * @return string   name or empty string, if not exist
     */
    public function getNameFromBIC(string $strBIC, bool $bToUCWords = false) : string
    {
        $strName = '';
        $oNode = $this->getProviderNode($strBIC);
        if ($oNode !== null) {
            $strName = ($bToUCWords ? $this->convToUCWords($oNode->nodeValue) : $oNode->nodeValue);
        }
        return $strName;
    }

    /**
     * Get the list of provider names.
     * Since the provider names in the supported directory all  in uppercase, this
     * can be converted to upper case words by setting the `$bToUCWords`parameter to true.
     * @param string $strCC country code the list should be generated for (leave empty for full list)
     * @param bool $bToUCWords convert the provider names to Uppercase Words
     * @return array<string>
     */
    public function getProviderList(string $strCC = '', bool $bToUCWords = false) : array
    {
        $aList = [];
        if ($this->oDoc !== null) {
            $oXPath = new \DOMXPath($this->oDoc);
            if (strlen($strCC) > 0) {
                $oNodelist = $oXPath->query("//Provider[@CC='" . $strCC . "']");
            } else {
                $oNodelist = $oXPath->query("//Provider");
            }
            if ($oNodelist !== false) {
                foreach ($oNodelist as $oNode) {
                    if ($oNode instanceof \DOMElement && $oNode->hasAttribute('BIC')) {
                        $aList[$oNode->getAttribute('BIC')] = ($bToUCWords ? $this->convToUCWords($oNode->nodeValue) : $oNode->nodeValue);
                    }
                }
            }
        }
        return $aList;
    }

    /**
     * Load the actual list from the internet.
     * Since downloading and saving the data takes a certain amount of time and this
     * list does not change constantly, an interval can be specified so that the data
     * only is downloaded again after it has expired.
     * The intervall can be specified as integer in seconds (according to a unix timestamp)
     * or as string representing any `dateinterval´.
     * > recommended values are 1..4 weeks (e.g. `'P2W'` for 2 weeks).
     * @link https://www.php.net/manual/dateinterval.construct.php
     * @param int|string|null $interval
     * @return bool
     */
    public function loadFromInternet($interval = null) : bool
    {
        try {
            if (!$this->hasIntervalExpired($interval)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->strLastError = $e->__toString();
            return false;
        }
        $this->oDoc = new \DOMDocument('1.0', 'UTF-8');

        $this->oDoc->formatOutput = true;
        $this->oDoc->preserveWhiteSpace = false;

        $xmlRoot = $this->oDoc->createElement("SCL-Directory");
        $xmlRoot->setAttribute('created', date('Y-m-d H:i:s'));
        $this->oDoc->appendChild($xmlRoot);

        $xmlBase = $this->oDoc->createElement('Providers');
        $xmlRoot->appendChild($xmlBase);

        // cURL
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => self::DATA_PROVIDER_URL,
            CURLOPT_HTTPHEADER     => ['User-Agent: cURL'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $strResponse = curl_exec($curl);
        if (is_bool($strResponse)) {
            $this->strLastError = 'cURL-Error: ' . curl_error($curl);
            curl_close($curl);
            return false;
        }
        curl_close($curl);
        $fp = fopen('data://text/plain,' . $strResponse, 'r');
        $aCountry = [];
        if ($fp !== false) {
            $iRow = 0;
            while (($row = fgetcsv($fp, 1000, ';')) !== false) {
                // first line contains 'valid from xx.xx.xxxx'; second the colheader
                if ($iRow > 1 && is_array($row) && count($row) > 1) {
                    $strBIC = trim($row[0]);
                    $strCC = substr($strBIC, 4, 2);
                    // $strName = ucwords(strtolower(trim($row[1])));
                    $strName = trim($row[1]);
                    $strName = utf8_encode($strName);
                    $strName = htmlspecialchars($strName);

                    $xmlRow = $this->oDoc->createElement('Provider', $strName);
                    $xmlRow->setAttribute('CC', $strCC);
                    $xmlRow->setAttribute('BIC', $strBIC);
                    $xmlBase->appendChild($xmlRow);

                    isset($aCountry[$strCC]) ? $aCountry[$strCC] = $aCountry[$strCC] + 1 : $aCountry[$strCC] = 1;
                }
                $iRow++;
            }
        }
        ksort($aCountry);
        $xmlBase = $this->oDoc->createElement('Countries');
        $xmlRoot->appendChild($xmlBase);
        foreach ($aCountry as $strCC => $iCount) {
            $xmlRow = $this->oDoc->createElement('Country', (string)$iCount);
            $xmlRow->setAttribute('CC', $strCC);
            $xmlBase->appendChild($xmlRow);
        }
        $strXMLName = self::DATAFILE;
        if (strlen($this->strDataPath) > 0) {
            $strXMLName = $this->strDataPath . DIRECTORY_SEPARATOR . self::DATAFILE;
        }
        $this->oDoc->save($strXMLName);
        return true;
    }

    /**
     * Return last error occured.
     * @return string
     */
    public function getError() : string
    {
        return $this->strLastError;
    }

    /**
     * Search for the node to the given BIC.
     * If the requested BIC ends with 'XXX' and does'n exist, we're lookin
     * for an entry without trailing 'XXX'.
     * @param string $strBIC
     * @return \DOMNode
     */
    protected function getProviderNode(string $strBIC) : ?\DOMNode
    {
        $oNode = null;
        if ($this->oDoc !== null) {
            $oXPath = new \DOMXPath($this->oDoc);
            $oNodelist = $oXPath->query("//Provider[@BIC='" . $strBIC . "']");
            if (($oNodelist === false || $oNodelist->length === 0) && strlen($strBIC) == 11 && substr($strBIC, 8, 3) == 'XXX') {
                $strBIC = substr($strBIC, 0, 8);
                $oNodelist = $oXPath->query("//Provider[@BIC='" . $strBIC . "']");
            }
            if ($oNodelist !== false && $oNodelist->length > 0) {
                $oNode = $oNodelist[0];
            }
        }
        return $oNode;
    }

    /**
     * Check, if the intervall has expired since last download.
     * The intervall can be specified as integer in seconds (according to a unix timestamp)
     * or as string representing any `dateinterval´.
     * > recommended values are 1..4 weeks (e.g. `'P2W'` for 2 weeks).
     * @param int|string|null $interval
     * @return bool
     * @throws \Exception if $interval cannot be parsed in the \DateInterval constructor
     */
    protected function hasIntervalExpired($interval = null) : bool
    {
        if ($interval === null) {
            return true;
        }
        $uxtsLastUpdate = $this->lastUpdated();
        if ($uxtsLastUpdate == 0) {
            return true;
        }
        if (is_numeric($interval)) {
            // inteval is a timespan in seconds...
            return $uxtsLastUpdate + $interval < time();
        } else {
            $di = new \DateInterval($interval);
            $dtLastUpdate = new \DateTime();
            $dtLastUpdate->setTimestamp($uxtsLastUpdate);
            $dtLastUpdate->add($di);

            return $dtLastUpdate->getTimestamp() < time();
        }
    }

    /**
     * Convert to uppercase words.
     * Some exceptions are converted to lowercase, some to uppercase and some
     * will be replaced by special case...
     * @param string $strText
     * @return string
     */
    protected function convToUCWords(string $strText)
    {
        $aDelimiters = [" ", "-", ".", "'"];
        // we use associative array because the 'isset' call works faster than the 'in_array' call
        // (And we expect a lot of calls or loops when using the getProviderList function...)
        $aToLower = ["a" => 1, "ab" => 1, "de" => 1, "der" => 1, "di" => 1, "do" => 1, "du" => 1, "et" => 1, "for" => 1, "im" => 1, "of" => 1, "on" => 1, "plc" => 1, "s" => 1, "und" => 1, "van" => 1, "von" => 1];
        $aToUpper = ["ABC" => 1, "AG" => 1, "BCP" => 1, "BGL" => 1, "BHF" => 1, "BKS" => 1, "BLG" => 1, "BNP" => 1, "CIB" => 1, "GB" => 1, "HSBC" => 1, "KG" => 1, "LGT" => 1, "NV" => 1, "SA" => 1, "UK" => 1, "VR" => 1];
        $aReplacement = ['eg' => 'eG', 'gmbh' => 'GmbH'];
        /*
         * Exceptions in lower case are words you don't want converted
         * Exceptions all in upper case are any words you don't want converted to title case
         *   but should be converted to upper case, e.g.:
         *   king henry viii or king henry Viii should be King Henry VIII
         */
        $strText = strtolower($strText);
        foreach ($aDelimiters as $delimiter) {
            $aWords = explode($delimiter, $strText);
            $aNewWords = [];
            foreach ($aWords as $strWord) {
                if (isset($aReplacement[$strWord])) {
                    // check replacements
                    $strWord = $aReplacement[$strWord];
                } elseif (isset($aToUpper[strtoupper($strWord)])) { // (in_array(strtoupper($strWord), $aToUpper)) {
                    // check list for any words that should be in upper case
                    $strWord = strtoupper($strWord);
                } elseif (!isset($aToLower[$strWord])) { // (!in_array($strWord, $aToLower)) {
                    // convert to UCFirst
                    $strWord = ucfirst($strWord);
                }
                $aNewWords[] = $strWord;
            }
            $strText = join($delimiter, $aNewWords);
        }//foreach
        return $strText;
    }
}