<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for french IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> FR14 2004 1010 0505 0001 3M02 606 </td></tr>
 * <tr><td>   BIC    </td><td> PARBFRPP757 </td></tr>
 * <tr><td>   CI     </td><td> FR72 ZZZ 123456 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbb bsss sskk kkkk kkkk kPP `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> 5 digits numeric banking code </td></tr>
 * <tr><td>   s      </td><td> 5 digits numeric branch code </td></tr>
 * <tr><td>   k      </td><td> 11 digits alphanumeric account number </td></tr>
 * <tr><td>   PP     </td><td> 2 digits numeric national check code </td></tr>
 * </tbody></table>
 *
 * Length: 27
 *
 * #### CI format
 *  The SEPA Identifier for creditors located in France is called
 *  'Identifiant Créancier SEPA' or 'ICS'. The Creditor Identifier
 *  (CI) has a total length of 13 characters. The country specific
 *  part of CI consists of 6 alphanumeric characters, based on
 *  hexadecimal classification.
 *
 * #### ` CCpp ZZZ xxxxxx `
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits alphanum creditor business code (CBC) </td></tr>
 * <tr><td>   x      </td><td> 6 digits hexadecimal national identification code </td></tr>
 * </tbody></table>
 *
 * Length: 13
 *
 * <b>All validation can be done with specification of length and regex to match format! </b>
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationFR extends SepaCntryValidationBase
{
    /**
     * Create instance of french validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
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