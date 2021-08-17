<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationBE;

/**
 * SepaCntryValidationBE test case.
 */
class SepaCntryValidationBETest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;

    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationBE('BE');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationBE('DE');
    }

    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationBE('BE');
        $this->assertSame($this->oValidation->validateIBAN('BE68 5390 0754 7034'), 0);
        $this->assertSame($this->oValidation->validateIBAN('BE68 5590 0754 7034'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('BE68 5390 0754 7034 20'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE68 5390 0754 7034'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('BE68 5A90 0754 7034'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationBE('BE');
        $this->assertSame($this->oValidation->validateBIC('JCAEBE9AXXX'), 0);
    }

    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationBE('BE');
        $this->assertSame($this->oValidation->validateCI('BE69 ZZZ 050 D 000000008'), 0);
        $this->assertSame($this->oValidation->validateCI('BE68 ZZZ 0123456789'), 0);
        $this->assertSame($this->oValidation->validateCI('BE31 ZZZ 0123456789'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('BE68 ZZZ 01234567890'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE61 ZZZ 0123456789'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('BE68 ZZZ A123456789'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

