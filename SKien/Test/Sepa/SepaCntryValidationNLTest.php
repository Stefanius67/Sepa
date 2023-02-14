<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationNL;

/**
 * SepaCntryValidationNL test case.
 */
class SepaCntryValidationNLTest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;

    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationNL('NL');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationNL('AT');
    }

    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationNL('NL');
        $this->assertSame($this->oValidation->validateIBAN('NL45 SNSB 0787 7543 90'), 0);
        $this->assertSame($this->oValidation->validateIBAN('NL35 SNSB 0787 7543 90'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('NL45 SNSB 0787 7543 0'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE45 SNSB 0787 7543 90'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('NL45 SNSB 0787 754A 90'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationNL('NL');
        $this->assertSame($this->oValidation->validateBIC('SNSBNL2A'), 0);
        $this->assertSame($this->oValidation->validateBIC('SNSBNL2AXXX'), 0);
        $this->assertSame($this->oValidation->validateBIC('SNSBDE2AXXX'), Sepa::ERR_BIC_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateBIC('S1SBNL2AXXX'), Sepa::ERR_BIC_INVALID);
    }

    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationNL('NL');
        $this->assertSame($this->oValidation->validateCI('NL21 ZZZ 123456789012'), 0);
        $this->assertSame($this->oValidation->validateCI('NL12 ZZZ 123456789012'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('NL50 ZZZ 12345678901'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE50 ZZZ 123456789012'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('NL50 ZZZ 12A456789012'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

