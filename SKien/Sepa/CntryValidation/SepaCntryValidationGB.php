<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Validation class for great britain IBAN and CI
 *
 * #### Valid testvalues
 * <table><tbody>
 * <tr><td>   IBAN   </td><td> GB29 NWBK 6016 1331 9268 19 </td></tr>
 * <tr><td>   BIC    </td><td> BKENGB54XXX </td></tr>
 * <tr><td>   CI     </td><td> GB26 ZZZ SDD BKEN 000000012345678901234 </td></tr>
 * </tbody></table>
 *
 * #### IBAN format
 * #### ` CCpp bbbb bsss sskk kkkk kkkk kPP `
 * <table><tbody>
 * <tr><td>   CC     </td><td> ISO Country Code </td></tr>
 * <tr><td>   pp     </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   b      </td><td> 4 digits alpha banking code </td></tr>
 * <tr><td>   s      </td><td> 6 digits numeric branch code </td></tr>
 * <tr><td>   k      </td><td> 8 digits numeric account number </td></tr>
 * </tbody></table>
 *
 * Length: 22
 *
 * #### CI format
 *  The UK has chosen to develop a Creditor Identifier specifically for
 *  the SDD Schemes, based on the format set out in the SDD Scheme Rulebooks
 *  and Implementation Guides. Called the UK SEPA CI it is structured as follows:
 *
 * #### ` CCpp ZZZ sssbbbbuuuuuuccccccccccccccc `
 * <table><tbody>
 * <tr><td>   C      </td><td> ISO Country Code </td></tr>
 * <tr><td>   p      </td><td> 2 digits IBAN checksum </td></tr>
 * <tr><td>   Z      </td><td> digits alphanum creditor business code (CBC) </td></tr>
 * <tr><td>   s      </td><td> 3 digits alpha scheme code i.e. SDD </td></tr>
 * <tr><td>   b      </td><td> 4 digits alpha participant code i.e. the first four characters of the issuing Creditor Bank’s BIC </td></tr>
 * <tr><td>   u      </td><td> 6 digits numeric bacs service user number (SUN) if one exists or six zeros in the absence of a bacs SUN </td></tr>
 * <tr><td>   c      </td><td> 15 digits alphanum determined by the issuing Creditor Bank </td></tr>
 * </tbody></table>
 *
 * Length: 35
 *
 * <b>All validation can be done with specification of length and regex to match format! </b>
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class SepaCntryValidationGB extends SepaCntryValidationBase
{
    /**
     * Create instance of validation for great britain.
     * @param string $strCntry  2 sign country code
     */
    public function __construct(string $strCntry)
    {
        $this->strCntry = 'GB';
        $this->iLenIBAN = 22;
        $this->strRegExIBAN = '/^([A-Z]){2}([0-9]){2}([A-Z]){4}([0-9]){14}?$/';
        $this->bAlphaNumIBAN = true;
        $this->iLenCI = 35;
        $this->strRegExCI = '/^([A-Z]){2}([0-9]){2}([0-9A-Z]){3}([A-Z]){7}([0-9]){6}([0-9A-Z]){15}?$/';
        $this->bAlphaNumCI = true;

        parent::__construct(strtoupper($strCntry));
    }
}