<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for swiss IBAN and CI
 * 
 * ### Valid testvalues
 *  - IBAN:   CH18 0483 5029 8829 8100 0
 *  - BIC:    CRESCHZZ80A
 *  - CI:     CH79 ZZZ 12345678901
 *
 * ### IBAN format
 * ** CCpp bbbb bkkk kkkk kkkk k **
 *  - CC:    ISO Country Code
 *  - pp:    2 digits IBAN checksum
 *  - b:     banking code 5 digits
 *  - k:     account number 12 digits
 *      
 * Length: 21
 *
 * ### CI format
 * ** CCpp ZZZ nnnnnnnnnnnn **
 *  - C:    ISO Country Code
 *  - p:    2 digits IBAN checksum
 *  - Z:    3 digits alphanum creditor business code
 *  - n:    11 digits numeric national identification code 
 *      
 * Length: 18
 *
 * *** All validation can be done with specification of length and regex to match format! ***
 * 
 * ### History
 * ** 2020-05-21 **
 * - initial version
 *
 * @package SKien/Sepa
 * @since 1.1.0
 * @version 1.1.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationCH extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'CH';
        $this->iLenIBAN = 21;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){19}?$/';
        $this->iLenCI = 18;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){11}?$/';
        
        parent::__construct(strtoupper($strCntry));
    }
}