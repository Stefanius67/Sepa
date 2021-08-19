<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for austrian IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> AT61 1904 3002 3457 3201 </td></tr>
 * <tr><td>   BIC    </td><td> RVVGAT2B468 </td></tr>
 * <tr><td>   CI     </td><td> AT61 ZZZ 01234567890 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbb bkkk kkkk kkkk k `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> banking code 5 digits </td></tr>
 * <tr><td>   k      </td><td> account number 11 digits </td></tr>
 * </tbody></table>
 *
 * Length: 20
 *
 * #### CI format
 * #### ` CCpp ZZZ 0nnnnnnnnnn `
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits alphanum creditor business code </td></tr>
 * <tr><td>   0      </td><td> 1 digit fixed value 0 </td></tr>
 * <tr><td>   n      </td><td> 10 digits numeric national identification code </td></tr>
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
class SepaCntryValidationAT extends SepaCntryValidationBase
{
    /**
     * Create instance of austrian validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'AT';
        $this->iLenIBAN = 20;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){18}?$/';
        $this->iLenCI = 18;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){11}?$/';

        parent::__construct(strtoupper($strCntry));
    }
}