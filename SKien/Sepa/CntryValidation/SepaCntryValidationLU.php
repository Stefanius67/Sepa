<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for luxembourgian IBAN and CI.
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> LU28 0019 4006 4475 0000 </td></tr>
 * <tr><td>   BIC    </td><td> BSUILULLREG </td></tr>
 * <tr><td>   CI     </td><td> ??? </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbk kkkk kkkk kkkk k `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 3 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> 3 digits banking code </td></tr>
 * <tr><td>   k      </td><td> 13 digits account number </td></tr>
 * </tbody></table>
 *
 * Length: 20
 *
 * #### CI format
 * #### `CCpp ZZZ 0nnnnnnnnnnnnnnnnnn `
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits alphanum creditor business code </td></tr>
 * <tr><td>   0      </td><td> 1 digit fixed value 0 </td></tr>
 * <tr><td>   n      </td><td> 18 digits alphanumeric national identification code </td></tr>
 * </tbody></table>
 *
 * Length: 26
 *
 * <b>All validation can be done with specification of length and regex to match format! </b>
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationLU extends SepaCntryValidationBase
{
    /**
     * Create instance of luxembourgian validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'LU';
        $this->iLenIBAN = 20;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){18}?$/';
        $this->iLenCI = 26;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}0([0-9A-Z]){18}?$/';
        $this->bAlphaNumCI = true;

        parent::__construct(strtoupper($strCntry));
    }
}