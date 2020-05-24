<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Interface for the country specific validation classes.
 * Classes to define the country specific validation of IBAN, BIC and
 * CI must implement this interfaace.
 * 
 * history:
 * date         version
 * 2020-05-21   initial version
 * 
 * @package SKien/Sepa
 * @version 1.1.0
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
interface SepaCntryValidation
{
    /**
     * create instance of validation.
     * @param string $strCntry  2 sign country code (ISO 3166-1)
     */
    public function __construct($strCntry);
    
    /**
     * validates given IBAN.
     * @return number OK ( 0 ) or errorcode
     */
    public function validateIBAN($strIBAN);
    
    /**
     * validates given BIC.
     * @param string $strBIC
     * @return number OK ( 0 ) or errorcode
     */
    public function validateBIC($strBIC);
    
    /**
     * validates given CI (Creditor Scheme Identification).
     * @param string $strCI
     * @return number OK ( 0 ) or errorcode
     */
    public function validateCI($strCI);
}