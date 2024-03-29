<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationLU;

/**
 * SepaCntryValidationLU test case.
 */
class SepaCntryValidationLUTest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;

    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationLU('LU');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationLU('DE');
    }

    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationLU('LU');
        $this->assertSame($this->oValidation->validateIBAN('LU28 0019 4006 4475 0000'), 0);
        $this->assertSame($this->oValidation->validateIBAN('LU21 0019 4006 4475 0000'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('LU28 019 4006 4475 0000'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE28 0019 4006 4475 0000'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('LU28 x019 4006 4475 0000'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationLU('LU');
        $this->assertSame($this->oValidation->validateBIC('BSUILULLREG'), 0);
    }

    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationLU('LU');
        if ($this->oValidation->validateCI('LU02 ZZZ 0ABCDEFGHIJKL123488') != 0) {
            $this->assertSame($this->oValidation->getLastCheckSum(), 'XX');
        }
        $this->assertSame($this->oValidation->validateCI('LU02 ZZZ 0ABCDEFGHIJKL123488'), 0);
        $this->assertSame($this->oValidation->validateCI('LU22 ZZZ 0123456789ABCDEFGHI'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('LU13 ZZZ 123456789ABCDEFGHI'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE13 ZZZ 0123456789ABCDEFGHI'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('LU1X ZZZ 0123456789ABCDEFGHI'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

