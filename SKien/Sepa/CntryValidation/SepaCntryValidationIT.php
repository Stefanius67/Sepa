<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for italian IBAN and CI.
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> IT60 X054 2811 1010 0000 0123 456 </td></tr>
 * <tr><td>   BIC    </td><td> ??? </td></tr>
 * <tr><td>   CI     </td><td> ??? </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp Kbbb bbss sssk kkkk kkkk kkk `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   K      </td><td> 1 digit code for the country code </td></tr>
 * <tr><td>   b      </td><td> 5 characters from the bank's SWIFT / BIC </td></tr>
 * <tr><td>   s      </td><td> 5-digit code for the branch of the bank </td></tr>
 * <tr><td>   k      </td><td> 12-digit code for the account number </td></tr>
 * </tbody></table>
 *
 * Length: 27
 *
 * #### CI format
 * #### ` CCpp ZZZ nnnnnnnnnnnnnnnn `
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits alphanum creditor business code </td></tr>
 * <tr><td>   n      </td><td> 16 digits alphanumeric national identification code </td></tr>
 * </tbody></table>
 *
 * Length: 23
 *
 * <b> All validation can be done with specification of length and regex to match format! </b>
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationIT extends SepaCntryValidationBase
{
    /**
     * Create instance of italian validation.
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