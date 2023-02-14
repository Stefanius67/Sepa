<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for netherlands IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> NL45 SNSB 0787 7543 90 </td></tr>
 * <tr><td>   BIC    </td><td> SNSBNL2AXXX </td></tr>
 * <tr><td>   CI     </td><td> NL50 ZZZ 123456789012 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbb kkkk kkkk kk `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> bank identifier 4 alphanum digits </td></tr>
 * <tr><td>   k      </td><td> account number 10 digits </td></tr>
 * </tbody></table>
 *
 * Length: 18
 *
 * #### CI format
 * #### ` CCpp ZZZ nnnnnnnn kkkk
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits CI checksum </td></tr>
 * <tr><td>   Z      </td><td> 3 digits creditor business code </td></tr>
 * <tr><td>   n      </td><td> 8 digits trade register number (KvK number) of the creditor </td></tr>
 * <tr><td>   k      </td><td> 4 digits numerical code as issued or agreed by the creditor bank </td></tr>
 * </tbody></table>
 *
 * Length: 19
 *
 * <b>All validation can be done with specification of length and regex to match format! </b>
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationNL extends SepaCntryValidationBase
{
    /**
     * Create instance of german validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'NL';
        $this->iLenIBAN = 18;
        $this->bAlphaNumIBAN = true;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){4}([0-9]){10}?$/';
        $this->iLenCI = 19;
        $this->bAlphaNumCI = true;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){12}?$/';

        parent::__construct(strtoupper($strCntry));
    }
}