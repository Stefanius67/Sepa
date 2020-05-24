<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for french IBAN and CI
 *
 * valid testvalues:
 * -----------------
 *  IBAN:   FR14 2004 1010 0505 0001 3M02 606
 *  BIC:    ???
 *  CI:     ???
 *
 * IBAN format:
 * ------------
 *  CCpp bbbb bsss sskk kkkk kkkk kPP
 *      CC:    ISO Country Code
 *      pp:    two-digit IBAN checksum
 *      b:     5 digits numeric banking code 
 *      s:     5 digits numeric branch code
 *      k:     11 digits alphanumeric account number
 *      PP:    2 digits numeric national check code
 *  Length: 27
 *
 *  CI format:
 *  -----------
 *  The SEPA Identifier for creditors located in France is called 
 *  'Identifiant Créancier SEPA' or 'ICS'. The Creditor Identifier 
 *  (CI) has a total length of 13 characters. The country specific 
 *  part of CI consists of 6 alphanumeric characters, based on 
 *  hexadecimal classification.
 *  
 *  CCpp ZZZ xxxxxx
 *      C:    ISO Country Code
 *      p:    two-digit IBAN checksum
 *      Z:    3 digits alphanum creditor business code (CBC)
 *      x:    6 digits hexadecimal national identification code
 *  Length: 13
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
class SepaCntryValidationFR extends SepaCntryValidationBase
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct($strCntry)
    {
        $this->strCntry = 'FR';
        $this->iLenIBAN = 27;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){12}([0-9A-Z]){11}([0-9]){2}?$/';
        $this->bAlphaNumIBAN = true;
        $this->iLenCI = 13;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9A-F]){6}?$/';
        $this->bAlphaNumCI = true;
        
        parent::__construct(strtoupper($strCntry));
    }
}