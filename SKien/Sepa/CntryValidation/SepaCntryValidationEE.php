<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for estonian IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> EE38 2200 2210 2014 5685 </td></tr>
 * <tr><td>   BIC    </td><td> RIKOEE22CBC </td></tr>
 * <tr><td>   CI     </td><td> EE43 ZZZ EE 00012345678 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbss kkkk kkkk kkkP `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> 2 digits numeric banking code </td></tr>
 * <tr><td>   a      </td><td> 2 digits numeric length </td></tr>
 * <tr><td>   k      </td><td> 11 digits numeric account number </td></tr>
 * <tr><td>   P      </td><td> 1 digit numeric internal check sum </td></tr>
 * </tbody></table>
 *
 * Length: 20
 *
 * #### CI format
 * #### ` CCpp ZZZ cc nnnnnnnnnnn `
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits alphanum creditor business code </td></tr>
 * <tr><td>   c      </td><td> 2 digits ISO country code of the following registry or ID code’s issuer </td></tr>
 * <tr><td>   n      </td><td> 11 digits numeric national identification code </td></tr>
 * </tbody></table>
 *
 * Length: 20
 *
 * <b>All validation can be done with specification of length and regex to match format! </b>
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationEE extends SepaCntryValidationBase
{
    /**
     * Create instance of estonian validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
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