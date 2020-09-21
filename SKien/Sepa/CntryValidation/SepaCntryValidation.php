<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Interface for the country specific validation classes.
 * Classes to define the country specific validation of IBAN, BIC and
 * CI must implement this interfaace.
 * 
 * #### History:
 * - *2020-05-21*   initial version.
 * - *2020-07-22*   added missing PHP 7.4 type hints / docBlock changes 
 *
 * @package SKien/Sepa
 * @since 1.1.0
 * @version 1.2.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
interface SepaCntryValidation
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code (ISO 3166-1)
     */
    public function __construct(string $strCntry);
    
    /**
     * validates given IBAN.
     * @param string $strIBAN
     * @return int OK ( 0 ) or errorcode
     */
    public function validateIBAN(string $strIBAN) : int;
    
    /**
     * validates given BIC.
     * @param string $strBIC
     * @return int OK ( 0 ) or errorcode
     */
    public function validateBIC(string $strBIC) : int;
    
    /**
     * validates given CI (Creditor Scheme Identification).
     * @param string $strCI
     * @return int OK ( 0 ) or errorcode
     */
    public function validateCI(string $strCI) : int;
}