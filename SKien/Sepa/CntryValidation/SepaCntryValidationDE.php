<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for german IBAN and CI
 * 
 * valid testvalues:
 * -----------------
 *  IBAN:   DE11 6829 0000 0009 2158 08
 *  BIC:    GENODE61LAH
 *  CI:     DE79 ZZZ 01234567890 / DE98 ZZZ 09999999999
 * 
 * IBAN format:
 * ------------
 *  CCpp bbbb bbbb kkkk kkkk kk 
 *      CC:    ISO Country Code
 *      pp:    two-digit IBAN checksum
 *      b:     banking code 8 digits
 *      k:     account number 10 digits
 *  Length: 22
 *
 *  CI format:
 *  -----------
 *  CCpp ZZZ 0nnnnnnnnnn
 *      C:    ISO Country Code
 *      p:    two-digit IBAN checksum
 *      Z:    3 digits alphanum creditor business code
 *      0:    1 digit always 0 
 *      n:    10 digits numeric national identification code 
 *  Length: 18
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
class SepaCntryValidationDE extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'DE';
        $this->iLenIBAN = 22;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){20}?$/';
        $this->iLenCI = 18;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}0([0-9]){10}?$/';
        
        parent::__construct(strtoupper($strCntry));
    }
}