<?php
namespace SKien\Sepa\CntryValidation;

use SKien\Sepa\Sepa;

/**
 * Base class for country specific validation for IBAN, BIC and CI
 *
 * Needed methods to calculate checksum and check the format of
 * the values.
 * Format check is made with regular expressions that can be set to
 * the country specific rules.
 * 
 * Create for each country to support a class extending this class.
 * For most of the participating countries, it is sufficient to 
 * specify in the constructor the respective length, the formatting 
 * rule (RegEx) and the information whether alphanumeric characters 
 * are allowed.
 * 
 * If more complicated rules apply in a country, the respective 
 * method for validation can be redefined in the extended class in 
 * order to map this rule.
 * @see SepaCntryValidationBE class
 * 
 * #### History:
 * - *2020-05-21*   initial version.
 * - *2020-07-22*   added missing PHP 7.4 type hints / docBlock changes 
 *
 * @package SKien/Sepa
 * @since 1.1.0
 * @version 1.2.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationBase implements SepaCntryValidation
{
    /** @var string 2 digit ISO country code (ISO 3166-1)   */
    protected string $strCntry = '';
    /** @var int    length of the IBAN     */
    protected int $iLenIBAN = -1;
    /** @var string regular expression to validate the format of the IBAN    */
    protected string $strRegExIBAN = '';
    /** @var bool   if true, alphanum values in the IBAN allowed    */ 
    protected bool $bAlphaNumIBAN = false;
    /** @var int    length of the IC     */
    protected int $iLenCI = -1;
    /** @var string regular expression to validate the format of the CI    */
    protected string $strRegExCI = '';
    /** @var bool   if true, alphanum values in the CI allowed    */ 
    protected bool $bAlphaNumCI = false;
    /** @var string last calculated checksum    */
    protected string $strLastCheckSum = '';
    
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        if (strtoupper($strCntry) != $this->strCntry) {
            trigger_error('instanciation with invalid country ' . $strCntry . ' (expected ' . $this->strCntry . ')', E_USER_ERROR);
        }
    }
    
    /**
     * validates given IBAN.
     *
     * @link https://www.ecbs.org/iban.htm
     * @link http://www.pruefziffernberechnung.de/I/IBAN.shtml
     * 
     * @param string $strIBAN
     * @return int OK ( 0 ) or errorcode
     *      Sepa::ERR_IBAN_INVALID_CNTRY    invalid country code
     *      Sepa::ERR_IBAN_INVALID_LENGTH   invalid length
     *      Sepa::ERR_IBAN_INVALID_SIGN     IBAN contains invalid sign(s)
     *      Sepa::ERR_IBAN_CHECKSUM         wrong checksum
     */
    public function validateIBAN(string $strIBAN) : int
    {
        // toupper, trim and remove containing blanks
        $strIBAN = str_replace(' ', '', trim(strtoupper($strIBAN)));
        if (strlen($strIBAN) != $this->iLenIBAN) {
            return Sepa::ERR_IBAN_INVALID_LENGTH;
        }
        if (substr($strIBAN, 0, 2) != $this->strCntry) {
            return Sepa::ERR_IBAN_INVALID_CNTRY;
        }
        if (!preg_match($this->strRegExIBAN, $strIBAN)) {
            return Sepa::ERR_IBAN_INVALID_SIGN;
        }
        
        $strCS = substr($strIBAN, 2, 2);
        $strBBAN = substr($strIBAN, 4);
        
        // alphanumeric account number allowed (except the country code...)?
        if ($this->bAlphaNumIBAN) {
            $strBBAN = $this->replaceAlpha($strBBAN);
        }
        if ($this->getCheckSum($strBBAN) != $strCS) {
            return Sepa::ERR_IBAN_CHECKSUM;
        }
        return Sepa::OK;
    }

    /**
     * Validates given BIC.
     * ISO 9362
     * as far as I have determined, the format of the BIC is uniform within
     * the participating countries.
     * @param string $strBIC
     * @return int OK ( 0 ) or errorcode
     */
    public function validateBIC(string $strBIC) : int
    {
        if (substr($strBIC, 4, 2) != $this->strCntry) {
            return Sepa::ERR_BIC_INVALID_CNTRY;
        }
        $iErr = Sepa::ERR_BIC_INVALID;
        if (preg_match('/^([A-Z]){4}([A-Z]){2}([0-9A-Z]){2}([0-9A-Z]{3})?$/', $strBIC)) {
            $iErr = Sepa::OK;
        }
        return $iErr;
    }
        
    /**
     * Validates given CI (Creditor Scheme Identification).
     * 
     * The general structure for the CI is the following:
     * - Position 1-2 filled with the ISO country code
     * - Position 3-4 filled with the check digit according to ISO 7064 Mod 97-10
     * - Position 5-7 filled with the Creditor Business Code, if not used then filled with ZZZ
     * - Position 8 onwards filled with the country specific part of the identifier being 
     *   a national identifier of the Creditor as defined by the concerned national community.
     *   
     * NOTE: the CBC is not taken into account when calculating the checksum!
     *   
     * @link https://www.sepaforcorporates.com/sepa-direct-debits/sepa-creditor-identifier/
     * @link https://www.europeanpaymentscouncil.eu/sites/default/files/kb/file/2019-09/EPC262-08%20v7.0%20Creditor%20Identifier%20Overview_0.pdf
     * @link https://www.europeanpaymentscouncil.eu/sites/default/files/KB/files/EPC114-06%20SDD%20Core%20Interbank%20IG%20V9.0%20Approved.pdf#page=10
     * 
     * online CI Validator
     * @link http://www.maric.info/fin/SEPA/ddchkden.htm
     * 
     * @param string $strCI
     * @return int OK ( 0 ) or errorcode
     *     Sepa::ERR_CI_INVALID_CNTRY      invalid country code.
     *     Sepa::ERR_CI_INVALID_LENGTH     invalid length.
     *     Sepa::ERR_CI_INVALID_SIGN       CI contains invalid sign(s).
     *     Sepa::ERR_CI_CHECKSUM           wrong checksum.
     */
    public function validateCI(string $strCI) : int
    {
        // toupper, trim and remove containing blanks
        $strCheck = str_replace(' ', '', trim(strtoupper($strCI)));
        if (strlen($strCheck) != $this->iLenCI) {
            return Sepa::ERR_CI_INVALID_LENGTH;
        }
        if (substr($strCheck, 0, 2) != $this->strCntry) {
            return Sepa::ERR_CI_INVALID_CNTRY;
        }
        if (!preg_match($this->strRegExCI, $strCheck)) {
            return Sepa::ERR_CI_INVALID_SIGN;
        }
        
        $strCS = substr($strCheck,  2, 2);
        // NOTE: the CBC is not taken into account when calculating the checksum!
        $strCheck = substr($strCheck,  7);
        if ($this->bAlphaNumCI) {
            $strCheck = $this->replaceAlpha($strCheck);
        }
        if ($this->getCheckSum($strCheck) != $strCS) {
            return Sepa::ERR_CI_CHECKSUM;
        }
        return Sepa::OK;
    }

    /**
     * Return last calculated checksum.
     * @return string
     */
    public function getLastCheckSum() : string
    {
        return $this->strLastCheckSum;
    }

	/**
     * calculate modulo 97 checksum for bankcode and accountnumber
     * MOD 97-10 (see ISO 7064)
     * @param string $strCheck
     * @return string
     */
    protected function getCheckSum(string $strCheck) : string
    {
        // calculate checksum
        // 1. move 6 digit feft and add numerical countrycode
        $strCS1 = $this->adjustFP(bcadd(bcmul($strCheck, '1000000'), $this->getAlpha2CntryCode() . '00'));
        // 2. modulo 97 value
        $strCS2 = $this->adjustFP(bcmod($strCS1, '97'));
        // 3. subtract value from 98
        $strCS = $this->adjustFP(bcsub('98', $strCS2));
        // 4. always 2 digits...
        if (strlen($strCS) < 2 ) {
            $strCS = '0' . $strCS;
        }
        $this->strLastCheckSum = $strCS;
        return $strCS;
    }
    
    /**
     * in some cases there appears unwanted decimals (floatingpoint drift from bc - operations)
     * ... just cut them off
     *
     * @param string $str
     * @return string
     */
    protected function adjustFP(string $str) : string
    {
        if( strpos( '.', $str ) !== false ) {
            $str = substr($str, 0, strpos('.', $str));
        }
        return $str;
    }
    
    /**
     * Get the ALPHA-2 country code.
     * To calc the checksum, the first four digits of the IBAN (country code and check digit) 
     * have to be placed at the end of the IBAN. Check digit is represented by 00 for calculation.
     * @return string
     */
    protected function getAlpha2CntryCode() : string
    {
        $strNumCode = $this->convCharToNum($this->strCntry[0]) . $this->convCharToNum($this->strCntry[1]);
        return $strNumCode; 
    }

    /**
     * replace all alpha chars with the numeric substitution
     * @param string $strCheck
     * @return string
     */
    protected function replaceAlpha(string $strCheck) : string
    {
        // account number may contains characters
        foreach (range('A', 'Z') as $ch){
            $strCheck = str_replace((string)$ch, $this->convCharToNum((string)$ch), $strCheck);
        }
        return $strCheck;
    }
    
    /**
     * Existing non-numeric characters must be converted to a numeric value for the calculation.
     * 
     *  A = 10  F = 15  K = 20  P = 25  U = 30  Z = 35
     *  B = 11  G = 16  L = 21  Q = 26  V = 31
     *  C = 12  H = 17  M = 22  R = 27  W = 32
     *  D = 13  I = 18  N = 23  S = 28  X = 33
     *  E = 14  J = 19  O = 24  T = 29  Y = 34
     * 
     * @param string $ch
     * @return string
     */
    protected function convCharToNum(string $ch) : string
    {
        $iValue = ord($ch) - ord('A') + 10;
        return strval($iValue); 
    }
}