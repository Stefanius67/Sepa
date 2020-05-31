<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for austrian IBAN and CI
 * 
 * ### Valid testvalues
 *  - IBAN:   AT61 1904 3002 3457 3201
 *  - BIC:    
 *  - CI:     AT61 ZZZ 01234567890
 * 
 * ### IBAN format
 * ** CCpp bbbb bkkk kkkk kkkk k **
 *  - CC:    ISO Country Code
 *  - pp:    2 digits IBAN checksum
 *  - b:     banking code 5 digits
 *  - k:     account number 11 digits
 *  
 * Length: 20
 *
 * ### CI format
 * ** CCpp ZZZ 0nnnnnnnnnn **
 *  - C:    ISO Country Code
 *  - p:    2 digits IBAN checksum
 *  - Z:    3 digits alphanum creditor business code
 *  - 0:    1 digit always 0 
 *  - n:    10 digits numeric national identification code
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
class SepaCntryValidationAT extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'AT';
        $this->iLenIBAN = 20;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){18}?$/';
        $this->iLenCI = 18;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){11}?$/';
        
        parent::__construct(strtoupper($strCntry));
    }
}