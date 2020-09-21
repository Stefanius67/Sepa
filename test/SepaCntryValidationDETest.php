<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationDE;
use SKien\Sepa\Sepa;

/**
 * oValidation test case.
 */
class SepaCntryValidationDETest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;
    
    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationDE('DE');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationDE('AT');
    }
    
    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationDE('DE');
        $this->assertSame($this->oValidation->validateIBAN('DE11 6829 0000 0009 2158 08'), 0);
        $this->assertSame($this->oValidation->validateIBAN('DE21 6829 0000 0009 2158 08'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('DE11 6829 0000 0009 2158 0'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('AT11 6829 0000 0009 2158 08'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('DE11 6829 000x 0009 2158 08'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationDE('DE');
        $this->assertSame($this->oValidation->validateBIC('GENODE61LAH'), 0);
        $this->assertSame($this->oValidation->validateBIC('GENOBE61LAH'), Sepa::ERR_BIC_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateBIC('GEN0DE61LAH'), Sepa::ERR_BIC_INVALID);
    }
    
    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationDE('DE');
        $this->assertSame($this->oValidation->validateCI('DE79 ZZZ 01234567890'), 0);
        $this->assertSame($this->oValidation->validateCI('DE71 ZZZ 01234567890'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('DE79 ZZZ 0123456789'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('BE79 ZZZ 01234567890'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('DE79 ZZZ 0123456789x'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

