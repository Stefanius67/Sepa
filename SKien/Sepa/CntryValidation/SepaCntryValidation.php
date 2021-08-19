<?php
namespace SKien\Sepa\CntryValidation;

/**
 * Interface for the country specific validation classes.
 * All classes to define the country specific validation of IBAN, BIC and
 * CI must implement this interface.
 *
 * @package Sepa
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
interface SepaCntryValidation
{
    /**
     * Create instance of validation.
     * @param string $strCntry  2 sign country code (ISO 3166-1)
     */
    public function __construct(string $strCntry);

    /**
     * Validates given IBAN.
     * @param string $strIBAN   IBAN to validate
     * @return int OK ( 0 ) or errorcode
     */
    public function validateIBAN(string $strIBAN) : int;

    /**
     * Validates given BIC.
     * @param string $strBIC    BIC to validate
     * @return int OK ( 0 ) or errorcode
     */
    public function validateBIC(string $strBIC) : int;

    /**
     * Validates given CI (Creditor Scheme Identification).
     * @param string $strCI     CI to validate
     * @return int OK ( 0 ) or errorcode
     */
    public function validateCI(string $strCI) : int;
}