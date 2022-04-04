<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationES;

/**
 * SepaCntryValidationES test case.
 */
class SepaCntryValidationESTest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;

    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationES('ES');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationES('AT');
    }

    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationES('ES');
        $this->assertSame($this->oValidation->validateIBAN('ES91 2100 0418 4502 0005 1332'), 0);
        $this->assertSame($this->oValidation->validateIBAN('ES81 2100 0418 4502 0005 1332'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('ES91 2100 0418 4502 0005 133'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('SS91 2100 0418 4502 0005 1332'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('ES91 2100 0418 4502 00a5 1332'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationES('ES');
        $this->assertSame($this->oValidation->validateBIC('NORTESMMXXX'), 0);
        $this->assertSame($this->oValidation->validateBIC('NORTDEMMXXX'), Sepa::ERR_BIC_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateBIC('NOR1ESMMXXX'), Sepa::ERR_BIC_INVALID);
    }

    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationES('ES');
        $this->assertSame($this->oValidation->validateCI('ES50 ZZZ M23456789'), 0);
        $this->assertSame($this->oValidation->validateCI('ES04 ZZZ 52840790N'), 0);
        $this->assertSame($this->oValidation->validateCI('ES59 ZZZ X1234567L'), 0);
        $this->assertSame($this->oValidation->validateCI('ES89 ZZZ M23456789'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('ES79 ZZZ M2345678'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE79 ZZZ M23456789'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('ES79 ZZZ Ma3456789'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

