<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\CntryValidation\SepaCntryValidationFR;
use SKien\Sepa\Sepa;

/**
 * oValidation test case.
 */
class SepaCntryValidationFRTest extends TestCase
{
    /** @var SepaCntryValidationBase     */
    private $oValidation;
    
    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationFR('FR');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationFR('DE');
    }
    
    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationFR('FR');
        $this->assertSame($this->oValidation->validateIBAN('FR14 2004 1010 0505 0001 3M02 606'), 0);
        $this->assertSame($this->oValidation->validateIBAN('FR16 2004 1010 0505 0001 3M02 606'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('FR14 204 1010 0505 0001 3M02 606'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE14 2004 1010 0505 0001 3M02 606'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('FR1X 2004 1010 0505 0001 3M02 606'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationFR('FR');
        $this->assertSame($this->oValidation->validateBIC('PARBFRPP757'), 0);
    }
    
    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationFR('FR');
        if ($this->oValidation->validateCI('FR72 ZZZ 123456') != 0) {
            $this->assertSame($this->oValidation->getLastCheckSum(), 'XX');
        }
        $this->assertSame($this->oValidation->validateCI('FR72 ZZZ 123456'), 0);
        $this->assertSame($this->oValidation->validateCI('FR12 ZZZ 123456'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('FR72 ZZZ 23456'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE72 ZZZ 123456'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('FR72 ZZZ X23456'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

