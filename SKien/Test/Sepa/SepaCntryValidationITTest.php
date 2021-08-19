<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationIT;

/**
 * SepaCntryValidationIT test case.
 */
class SepaCntryValidationITTest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;

    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationIT('IT');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationIT('DE');
    }

    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationIT('IT');
        $this->assertSame($this->oValidation->validateIBAN('IT60 X054 2811 1010 0000 0123 456'), 0);
        $this->assertSame($this->oValidation->validateIBAN('IT61 X054 2811 1010 0000 0123 456'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('IT60 X054 2811 1010 0000 0123 45'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE60 X054 2811 1010 0000 0123 456'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('IT60 X054 2811 1010 00A0 0123 456'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationIT('IT');
        $this->assertSame($this->oValidation->validateBIC('IBSPITNAXXX'), 0);
    }

    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationIT('IT');
        if ($this->oValidation->validateCI('IT66 ZZZ A1B2C3D4E5F6G7H8') != 0) {
            $this->assertSame($this->oValidation->getLastCheckSum(), 'XX');
        }
        $this->assertSame($this->oValidation->validateCI('IT66 ZZZ A1B2C3D4E5F6G7H8'), 0);
        $this->assertSame($this->oValidation->validateCI('IT92 ZZZ A1B2C3D4E5F6G7H8'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('IT66 ZZZ AB2C3D4E5F6G7H8'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE66 ZZZ A1B2C3D4E5F6G7H8'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('IT9A ZZZ A1B2C3D4E5F6G7H8'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

