<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationEE;
use SKien\Sepa\Sepa;

/**
 * oValidation test case.
 */
class SepaCntryValidationEETest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;
    
    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationEE('EE');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationEE('DE');
    }
    
    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationEE('EE');
        $this->assertSame($this->oValidation->validateIBAN('EE38 2200 2210 2014 5685'), 0);
        $this->assertSame($this->oValidation->validateIBAN('EE38 2200 2210 2013 5685'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('EE38 2200 2210 2014 568'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('BE38 2200 2210 2014 5685'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('EE38 2200 A210 2014 5685'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationEE('EE');
        $this->assertSame($this->oValidation->validateBIC('RIKOEE22CBC'), 0);
    }
    
    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationEE('EE');
        $this->assertSame($this->oValidation->validateCI('EE49 ZZZ EE 00012345678'), 0);
        if ($this->oValidation->validateCI('EE49 ZZZ EE 00012345678') != 0) {
            $this->assertSame($this->oValidation->getLastCheckSum(), 'XX');
        }
        $this->assertSame($this->oValidation->validateCI('EE23 ZZZ EE 00012345678'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('EE49 ZZZ EE 0012345678'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('BE49 ZZZ EE 00012345678'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('EE49 ZZZ E1 00012345678'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

