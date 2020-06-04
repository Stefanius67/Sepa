<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for estonian IBAN and CI
 * 
 * ### Valid testvalues
 *  - IBAN:   EE38 2200 2210 2014 5685
 *  - BIC:    RIKOEE22CBC
 *  - CI:     EE43 ZZZ EE 00012345678
 *
 * ### IBAN format
 * ** CCpp bbss kkkk kkkk kkkP **
 *  - CC:    ISO Country Code
 *  - pp:    2 digits IBAN checksum
 *  - b:     2 digits numeric banking code
 *  - a:     2 digits numeric length
 *  - k:     11 digits numeric account number
 *  - P:     1 digit numeric internal check sum
 *      
 * Length: 20
 *
 * ### CI format
 * ** CCpp ZZZ cc nnnnnnnnnnn **
 *  - C:    ISO Country Code
 *  - p:    2 digits IBAN checksum
 *  - Z:    3 digits alphanum creditor business code
 *  - c:    2 digits ISO country code of the following registry or ID code’s issuer
 *  - n:    11 digits numeric national identification code
 *      
 * Length: 20
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
class SepaCntryValidationEE extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'EE';
        $this->iLenIBAN = 20;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){18}?$/';
        $this->iLenCI = 20;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([A-Z]){2}([0-9]){11}?$/';
        $this->bAlphaNumCI = true;
        
        parent::__construct(strtoupper($strCntry));
    }
}