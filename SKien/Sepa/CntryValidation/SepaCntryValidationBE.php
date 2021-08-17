<?php
namespace SKien\Sepa\CntryValidation;

use SKien\Sepa\Sepa;
/**
 * Validation class for belgian IBAN and CI
 *
 * ### Valid testvalues
 *  - IBAN:   BE68 5390 0754 7034
 *  - BIC:    JCAEBE9AXXX
 *  - CI:     BE69 ZZZ 050 D 000000008 / BE68 ZZZ 0123456789
 *
 * ### IBAN format
 * ** CCpp bbbk kkkk kkPP **
 *  - CC:    ISO Country Code
 *  - pp:    2 digits IBAN checksum
 *  - b:     3 digits numeric banking code
 *  - k:     7 digits numeric account number
 *  - PP:    2 digits numeric national check code
 *
 * Length: 16
 *
 * ### CI format
 * See method SepaCntryValidationBE::validateCI()
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
class SepaCntryValidationBE extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'BE';
        $this->iLenIBAN = 16;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){14}?$/';

        parent::__construct(strtoupper($strCntry));
    }

    /**
     * Validates given CI for belgium.
     *
     * When the Creditor has an 'Enterprise Number'
     * --------------------------------------------
     *  CCpp ZZZ nnnnnnnnnn
     *      C:    ISO Country Code
     *      p:    2 digits IBAN checksum
     *      Z:    3 digits alphanum creditor business code (CBC)
     *      n:    10 digits numeric 'Enterprise Number'
     *  Length: 17
     *
     * For the national identifier 10 numeric positions fixed length are used.
     * It is called the 'Enterprise Number' (this number is also used as the VAT
     * Number by the company).
     *
     * When the Creditor does not have an 'Enterprise Number'
     * ------------------------------------------------------
     *  CCpp ZZZ bbbDnnnnnnnnn
     *      C:    ISO Country Code
     *      p:    2 digits IBAN checksum
     *      Z:    3 digits alphanum creditor business code (CBC)
     *      b:    3 digits numeric internal bank code (specific for Belgium)
     *      D:    1 digit fixed 'D' character
     *      n:    9 digits numeric increasing number issued by the Creditor Bank
     *  Length: 20
     *
     * @param string $strCI
     * @return int OK ( 0 ) or errorcode
     */
    public function validateCI(string $strCI) : int
    {
        // toupper, trim and remove containing blanks
        $strCheck = str_replace(' ', '', trim(strtoupper($strCI)));
        $strRegEx = '';
        $bAlphaNum = false;
        if (strlen($strCheck) == 17) {
            $strRegEx = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){10}?$/';
        } else if (strlen($strCheck) == 20) {
            $strRegEx = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){3}D([0-9]){9}?$/';
            $bAlphaNum = true;
        } else {
            return Sepa::ERR_CI_INVALID_LENGTH;
        }

        if (substr($strCheck, 0, 2) != $this->strCntry) {
            return Sepa::ERR_CI_INVALID_CNTRY;
        }
        if (!preg_match($strRegEx, $strCheck)) {
            return Sepa::ERR_CI_INVALID_SIGN;
        }
        $strCS = substr($strCheck, 2, 2);
        // NOTE: the CBC is not taken into account when calculating the checksum!
        $strCheck = substr($strCheck, 7);
        if ($bAlphaNum) {
            $strCheck = $this->replaceAlpha($strCheck);
        }
        if ($this->getCheckSum($strCheck) != $strCS) {
            return Sepa::ERR_CI_CHECKSUM;
        }
        return Sepa::OK;
    }
}