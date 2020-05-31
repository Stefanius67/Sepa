<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for great britain IBAN and CI
 *
 * ### Valid testvalues
 *  - IBAN:   GB29 NWBK 6016 1331 9268 19
 *  - BIC:    ???
 *  - CI:     ???
 *
 * ### IBAN format
 * ** CCpp bbbb bsss sskk kkkk kkkk kPP **
 *  - CC:    ISO Country Code
 *  - pp:    2 digits IBAN checksum
 *  - b:     4 digits alpha banking code 
 *  - s:     6 digits numeric branch code
 *  - k:     8 digits numeric account number
 *      
 * Length: 22
 *
 * ### CI format
 *  The UK has chosen to develop a Creditor Identifier specifically for 
 *  the SDD Schemes, based on the format set out in the SDD Scheme Rulebooks
 *  and Implementation Guides. Called the UK SEPA CI it is structured as follows:
 *  
 * ** CCpp ZZZ sssbbbbuuuuuuccccccccccccccc **
 *  - C:    ISO Country Code
 *  - p:    2 digits IBAN checksum
 *  - Z:    3 digits alphanum creditor business code (CBC)
 *  - s:    3 digits alpha scheme code i.e. SDD
 *  - b:    4 digits alpha participant code i.e. the first four characters of the issuing Creditor Bank’s BIC
 *  - u:    6 digits numeric bacs service user number (SUN) if one exists or six zeros in the absence of a bacs SUN
 *  - c:    15 digits alphanum determined by the issuing Creditor Bank
 *      
 * Length: 35
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
class SepaCntryValidationGB extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'GB';
        $this->iLenIBAN = 22;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){2}([A-Z]){4}([0-9]){14}?$/';
        $this->bAlphaNumIBAN = true;
        $this->iLenCI = 35;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([A-Z]){7}([0-9]){6}([0-9A-Z]){15}?$/';
        $this->bAlphaNumCI = true;
        
        parent::__construct(strtoupper($strCntry));
    }
}