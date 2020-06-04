<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\CntryValidation\SepaCntryValidationCH;
use SKien\Sepa\Sepa;

/**
 * oValidation test case.
 */
class SepaCntryValidationCHTest extends TestCase
{
    /** @var SepaCntryValidationBase     */
    private $oValidation;
    
    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationCH('CH');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationCH('DE');
    }
    
    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationCH('CH');
        $this->assertSame($this->oValidation->validateIBAN('CH18 0483 5029 8829 8100 0'), 0);
        $this->assertSame($this->oValidation->validateIBAN('CH11 0483 5029 8829 8100 0'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('CH18 0483 5029 8829 100 0'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE18 0483 5029 8829 8100 0'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('CH18 0483 5029 882c 8100 0'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationCH('CH');
        $this->assertSame($this->oValidation->validateBIC('CRESCHZZ80A'), 0);
        $this->assertSame($this->oValidation->validateBIC('CRESDHZZ80A'), Sepa::ERR_BIC_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateBIC('CR1SCHZZ80A'), Sepa::ERR_BIC_INVALID);
    }
    
    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationCH('CH');
        $this->assertSame($this->oValidation->validateCI('CH51 ZZZ 12345678901'), 0);
        $this->assertSame($this->oValidation->validateCI('CH71 ZZZ 12345678901'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('CH51 ZZZ 1345678901'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('SH51 ZZZ 12345678901'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('CH5x ZZZ 12345678901'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

