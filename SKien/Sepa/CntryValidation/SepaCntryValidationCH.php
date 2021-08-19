<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for swiss IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> CH18 0483 5029 8829 8100 0 </td></tr>
 * <tr><td>   BIC    </td><td> CRESCHZZ80A </td></tr>
 * <tr><td>   CI     </td><td> CH51 ZZZ 12345678901 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbb bkkk kkkk kkkk k `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> banking code 5 digits </td></tr>
 * <tr><td>   k      </td><td> account number 12 digits </td></tr>
 * </tbody></table>
 *
 * Length: 21
 *
 * #### CI format
 * #### ` CCpp ZZZ nnnnnnnnnnnn `
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits alphanum creditor business code </td></tr>
 * <tr><td>   n      </td><td> 11 digits numeric national identification code  </td></tr>
 * </tbody></table>
 *
 * Length: 18
 *
 * <b>All validation can be done with specification of length and regex to match format! </b>
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationCH extends SepaCntryValidationBase
{
    /**
     * Create instance of swiss validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'CH';
        $this->iLenIBAN = 21;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){19}?$/';
        $this->iLenCI = 18;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){11}?$/';

        parent::__construct(strtoupper($strCntry));
    }
}