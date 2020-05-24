<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for luxembourgeian IBAN and CI
 *
 * valid testvalues:
 * -----------------
 *  IBAN:   LU28 0019 4006 4475 0000
 *  BIC:    ???
 *  CI:     ???
 *
 * IBAN format:
 * ------------
 *  CCpp bbbk kkkk kkkk kkkk k
 *      CC:    ISO Country Code
 *      pp:    two-digit IBAN checksum
 *      b:     banking code 3 digits
 *      k:     account number 13 digits
 *  Length: 20
 *
 *  CI format:
 *  -----------
 *  CCpp ZZZ 0nnnnnnnnnnnnnnnnnn
 *      C:    ISO Country Code
 *      p:    two-digit IBAN checksum
 *      Z:    3 digits alphanum creditor business code
 *      0:    1 digit fixed value 0
 *      n:    18 digits alphanumeric national identification code 
 *  Length: 26
 *
 *  All validation can be done with specification of length and regex to match format!
 *
 * history:
 * date         version
 * 2020-05-21   initial version
 *
 * @package SKien/Sepa
 * @version 1.1.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationLU extends SepaCntryValidationBase
{
    /**
     * create instance of luxembourge validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'LU';
        $this->iLenIBAN = 20;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){18}?$/';
        $this->iLenCI = 26;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}0([0-9][A-Z]){18}?$/';
        $this->bAlphaNumCI = true;
        
        parent::__construct(strtoupper($strCntry));
    }
}