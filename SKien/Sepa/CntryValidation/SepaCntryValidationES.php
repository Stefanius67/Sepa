<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for spain IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> ES91 2100 0418 4502 0005 1332 </td></tr>
 * <tr><td>   BIC    </td><td> NORTESMMXXX </td></tr>
 * <tr><td>   CI     </td><td> ES50 ZZZ M23456789 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbb ssss KKkk kkkk kkkk `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> bank identifier 4 digits </td></tr>
 * <tr><td>   s      </td><td> branch identifier 4 digits </td></tr>
 * <tr><td>   k      </td><td> control code 2 digits </td></tr>
 * <tr><td>   k      </td><td> account number 10 digits </td></tr>
 * </tbody></table>
 *
 * Length: 22
 *
 * #### CI format
 * #### ` CCpp ZZZ l nnnnnnn c
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits CI checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits creditor business code </td></tr>
 * <tr><td>   l      </td><td> a letter indicating the legal entity </td></tr>
 * <tr><td>   n      </td><td> 7 digits – indicating province / legal entity </td></tr>
 * <tr><td>   c      </td><td> a letter/digit check code </td></tr>
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
class SepaCntryValidationES extends SepaCntryValidationBase
{
    /**
     * Create instance of german validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'ES';
        $this->iLenIBAN = 24;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){22}?$/';
        $this->iLenCI = 16;
        $this->bAlphaNumCI = true;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){4}([0-9]){7}([0-9A-Z]){1}?$/';

        parent::__construct(strtoupper($strCntry));
    }
}