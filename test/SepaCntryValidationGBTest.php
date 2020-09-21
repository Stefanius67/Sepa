<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\CntryValidation\SepaCntryValidation;
use SKien\Sepa\CntryValidation\SepaCntryValidationGB;
use SKien\Sepa\Sepa;

/**
 * oValidation test case.
 */
class SepaCntryValidationGBTest extends TestCase
{
    /** @var SepaCntryValidation     */
    private $oValidation;
    
    public function test__construct()
    {
        $this->oValidation = new SepaCntryValidationGB('GB');
        $this->expectError();
        $this->oValidation = new SepaCntryValidationGB('DE');
    }
    
    public function testValidateIBAN()
    {
        $this->oValidation = new SepaCntryValidationGB('GB');
        $this->assertSame($this->oValidation->validateIBAN('GB29 NWBK 6016 1331 9268 19'), 0);
        $this->assertSame($this->oValidation->validateIBAN('GB19 NWBK 6016 1331 9268 19'), Sepa::ERR_IBAN_CHECKSUM);
        $this->assertSame($this->oValidation->validateIBAN('GB29 NWBK 606 1331 9268 19'), Sepa::ERR_IBAN_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateIBAN('DE29 NWBK 6016 1331 9268 19'), Sepa::ERR_IBAN_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateIBAN('GB29 NWBK D016 1331 9268 19'), Sepa::ERR_IBAN_INVALID_SIGN);
    }

    public function testValidateBIC()
    {
        $this->oValidation = new SepaCntryValidationGB('GB');
        $this->assertSame($this->oValidation->validateBIC('BKENGB54XXX'), 0);
    }
    
    public function testValidateCI()
    {
        $this->oValidation = new SepaCntryValidationGB('GB');
        if ($this->oValidation->validateCI('GB26 ZZZ SDD BKEN 000000012345678901234') != 0) {
            $this->assertSame($this->oValidation->getLastCheckSum(), 'XX');
        }
        $this->assertSame($this->oValidation->validateCI('GB26 ZZZ SDD BKEN 000000012345678901234'), 0);
        $this->assertSame($this->oValidation->validateCI('GB13 ZZZ SDD BKEN 000000012345678901234'), Sepa::ERR_CI_CHECKSUM);
        $this->assertSame($this->oValidation->validateCI('GB26 ZZZ SDD BKEN 00000012345678901234'), Sepa::ERR_CI_INVALID_LENGTH);
        $this->assertSame($this->oValidation->validateCI('DE26 ZZZ SDD BKEN 000000012345678901234'), Sepa::ERR_CI_INVALID_CNTRY);
        $this->assertSame($this->oValidation->validateCI('GB26 ZZZ SDD 1KEN 000000012345678901234'), Sepa::ERR_CI_INVALID_SIGN);
    }
}

