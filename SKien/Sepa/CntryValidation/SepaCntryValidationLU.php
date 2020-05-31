<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for luxembourgian IBAN and CI
 *
 * ### Valid testvalues
 *  - IBAN:   LU28 0019 4006 4475 0000
 *  - BIC:    ???
 *  - CI:     ???
 *
 * ### IBAN format
 * ** CCpp bbbk kkkk kkkk kkkk k **
 *  - CC:    ISO Country Code
 *  - pp:    3 digits IBAN checksum
 *  - b:     3 digits banking code 
 *  - k:     13 digits account number 
 *      
 * Length: 20
 *
 * ### CI format
 * ** CCpp ZZZ 0nnnnnnnnnnnnnnnnnn **
 *  - C:    ISO Country Code
 *  - p:    2 digits IBAN checksum
 *  - Z:    3 digits alphanum creditor business code
 *  - 0:    1 digit fixed value 0
 *  - n:    18 digits alphanumeric national identification code 
 *      
 * Length: 26
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