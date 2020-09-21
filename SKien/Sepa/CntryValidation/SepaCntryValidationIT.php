<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for italian IBAN and CI
 * 
 * ### Valid testvalues
 *  - IBAN:   IT60 X054 2811 1010 0000 0123 456
 *  - BIC:    ???
 *  - CI:     ???
 * 
 * ### IBAN format
 * ** CCpp Kbbb bbss sssk kkkk kkkk kkk  **
 *  - CC:    ISO Country Code
 *  - pp:    2 digits IBAN checksum
 *  - K:     1 digit code for the country code
 *  - b:     5 characters from the bank's SWIFT / BIC
 *  - s:     5-digit code for the branch of the bank
 *  - k:     12-digit code for the account number 
 *  
 * Length: 27
 *
 * ### CI format
 * ** CCpp ZZZ nnnnnnnnnnnnnnnn **
 *  - C:    ISO Country Code
 *  - p:    2 digits IBAN checksum
 *  - Z:    3 digits alphanum creditor business code
 *  - n:    16 digits alphanum national identification code 
 *  
 * Length: 23
 *  
 * *** All validation can be done with specification of length and regex to match format! ***
 * 
 * #### History:
 * - *2020-07-22*   initial version. 
 *
 * @package SKien/Sepa
 * @since 1.2.0
 * @version 1.2.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationIT extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'IT';
        $this->iLenIBAN = 27;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){6}([0-9]){17}?$/';
        $this->bAlphaNumIBAN = true;
        $this->iLenCI = 23;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){19}?$/';
        $this->bAlphaNumCI = true;
        
        parent::__construct(strtoupper($strCntry));
    }
}