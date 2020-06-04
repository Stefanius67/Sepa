<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\CntryValidation\SepaCntryValidationAT;
use SKien\Sepa\Sepa;

/**
 * oValidation test case.
 */
class SepaCntryValidationATTest extends TestCase
{
    /** @var SepaCntryValidationBase     */
    private $oValidation;
    
    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationAT('AT');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationAT('DE');
    }
    
    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationAT('AT');
        $this->assertSame($this->oValidation->validateIBAN('AT61 1904 3002 3457 3201'), 0);
        $this->assertSame($this->oValidation->validateIBAN('AT31 1904 3002 3457 3201'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('AT61 1904 3002 3457 320'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE61 1904 3002 3457 3201'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('ATDE 1904 3002 3457 3201'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationAT('AT');
        $this->assertSame($this->oValidation->validateBIC('RVVGAT2B468'), 0);
        $this->assertSame($this->oValidation->validateBIC('RVVGAB2B468'), Sepa::ERR_BIC_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateBIC('R1VGAT2B468'), Sepa::ERR_BIC_INVALID);
    }
    
    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationAT('AT');
        $this->assertSame($this->oValidation->validateCI('AT61 ZZZ 01234567890'), 0);
        $this->assertSame($this->oValidation->validateCI('AT31 ZZZ 01234567890'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('AT61 ZZZ 0123456789'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE61 ZZZ 01234567890'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('AT61 ZZZ 0123A456789'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

