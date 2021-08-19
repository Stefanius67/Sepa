<?php
namespace SKien\Sepa\CntryValidation;

use SKien\Sepa\Sepa;
/**
 * Validation class for belgian IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> BE68 5390 0754 7034 </td></tr>
 * <tr><td>   BIC    </td><td> JCAEBE9AXXX </td></tr>
 * <tr><td>   CI     </td><td> BE69 ZZZ 050 D 000000008 / BE68 ZZZ 0123456789 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbk kkkk kkPP `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> 3 digits numeric banking code </td></tr>
 * <tr><td>   k      </td><td> 7 digits numeric account number </td></tr>
 * <tr><td>   PP     </td><td> 2 digits numeric national check code </td></tr>
 * </tbody></table>
 *
 * Length: 16
 *
 * #### CI format
 * Belgium has a more complex format for the CI. For more information
 * see method SepaCntryValidationBE::validateCI()
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationBE extends SepaCntryValidationBase
{
    /**
     * Create instance of belgian validation.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'BE';
        $this->iLenIBAN = 16;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){14}?$/';

        parent::__construct(strtoupper($strCntry));
    }

    /**
     * Validates given CI for belgium.
     *
     * In Belgium there are two different formats for the CI, depending on whether the holder
     * has an 'Enterprise' number or not
     *
     * #### 1. When the Creditor has an 'Enterprise Number'
     * #### ` CCpp ZZZ nnnnnnnnnn `
     * <table><tbody>
     * <tr><td>   C     </td><td> ISO Country Code </td></tr>
     * <tr><td>   p     </td><td> 2 digits IBAN checksum </td></tr>
     * <tr><td>   Z     </td><td> 3 digits alphanum creditor business code (CBC) </td></tr>
     * <tr><td>   n     </td><td> 10 digits numeric 'Enterprise Number' </td></tr>
     * </tbody></table>
     *
     * Length: 17
     *
     * <i>
     * For the national identifier 10 numeric positions fixed length are used.
     * It is called the 'Enterprise Number' (this number is also used as the VAT
     * number by the company).
     * </i>
     *
     * #### 2. When the Creditor does not have an 'Enterprise Number'
     * #### ` CCpp ZZZ bbbDnnnnnnnnn `
     * <table><tbody>
     * <tr><td>   C     </td><td> ISO Country Code </td></tr>
     * <tr><td>   p     </td><td> 2 digits IBAN checksum </td></tr>
     * <tr><td>   Z     </td><td> 3 digits alphanum creditor business code (CBC) </td></tr>
     * <tr><td>   b     </td><td> 3 digits numeric internal bank code (specific for Belgium) </td></tr>
     * <tr><td>   D     </td><td> 1 digit fixed 'D' character </td></tr>
     * <tr><td>   n     </td><td> 9 digits numeric increasing number issued by the Creditor Bank </td></tr>
     * </tbody></table>
     *
     * Length: 20
     *
     * <b>
     * The differentiation between the two formats is done through the length!
     * </b>
     *
     * @param string $strCI
     * @return int OK ( 0 ) or errorcode
     */
    public function validateCI(string $strCI) : int
    {
        // toupper, trim and remove containing blanks
        $strCheck = str_replace(' ', '', trim(strtoupper($strCI)));
        $strRegEx = '';
        $bAlphaNum = false;
        if (strlen($strCheck) == 17) {
            $strRegEx = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){10}?$/';
        } else if (strlen($strCheck) == 20) {
            $strRegEx = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([0-9]){3}D([0-9]){9}?$/';
            $bAlphaNum = true;
        } else {
            return Sepa::ERR_CI_INVALID_LENGTH;
        }

        if (substr($strCheck, 0, 2) != $this->strCntry) {
            return Sepa::ERR_CI_INVALID_CNTRY;
        }
        if (!preg_match($strRegEx, $strCheck)) {
            return Sepa::ERR_CI_INVALID_SIGN;
        }
        $strCS = substr($strCheck, 2, 2);
        // NOTE: the CBC is not taken into account when calculating the checksum!
        $strCheck = substr($strCheck, 7);
        if ($bAlphaNum) {
            $strCheck = $this->replaceAlpha($strCheck);
        }
        if ($this->getCheckSum($strCheck) != $strCS) {
            return Sepa::ERR_CI_CHECKSUM;
        }
        return Sepa::OK;
    }
}