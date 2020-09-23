<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\SepaPmtInf;
use SKien\Sepa\SepaDoc;
use SKien\Sepa\SepaTxInf;

/**
 * oValidation test case.
 */
class SepaPmtInfTest extends TestCase
{
    const validIBAN = 'DE11 6829 0000 0009 2158 08';
    const validBIC = 'GENODE61LAH';
    const validCI = 'DE79 ZZZ 01234567890';
    const invalidIBAN = 'DE21 6829 0000 0009 2158 08';
    const invalidBIC = 'GEN0DE61LAH';
    const invalidCI = 'DE49 ZZZ 01234567890';
    
    protected ?SepaDoc $oSD = null;
    
    public function test__construct()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = new SepaPmtInf($this->oSD);
        $this->assertNotEmpty($oPmtInf->getId());
    }
    
    public function testValidate()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = $this->createValidPmtInf();
        $this->assertEquals(self::validIBAN, $oPmtInf->getIBAN());
        $this->assertEquals(self::validBIC, $oPmtInf->getBIC());
        $this->assertEquals(self::validCI, $oPmtInf->getCI());
        $this->assertEquals('Testname', $oPmtInf->getName());
        $this->assertEquals(Sepa::SEQ_FIRST, $oPmtInf->getSeqType());
        
        $this->assertEquals(0, $oPmtInf->validate());
    }

    public function testValidateInvalid()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = new SepaPmtInf($this->oSD);
        $oPmtInf->setIBAN(self::invalidIBAN);
        $oPmtInf->setBIC(self::invalidBIC);
        $oPmtInf->setCI(self::invalidCI);
        $oPmtInf->setName('Testname');
        $oPmtInf->setSeqType(Sepa::SEQ_FIRST);
        
        $iExpectedErr = Sepa::ERR_PMT_INVALID_IBAN | Sepa::ERR_PMT_INVALID_BIC | Sepa::ERR_PMT_INVALID_CI;
        $this->assertEquals($iExpectedErr, $oPmtInf->validate());
        
        Sepa::setValidationLevel(Sepa::V_NO_IBAN_VALIDATION | Sepa::V_NO_BIC_VALIDATION | Sepa::V_NO_CI_VALIDATION);
        $this->assertEquals(0, $oPmtInf->validate());
    }
    
    public function testValidateMissing()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = new SepaPmtInf($this->oSD);
        
        $iExpectedErr = 
            Sepa::ERR_PMT_IBAN_MISSING | 
            Sepa::ERR_PMT_BIC_MISSING |
            Sepa::ERR_PMT_CI_MISSING |
            Sepa::ERR_PMT_NAME_MISSING
        ;
            
        $this->assertEquals($iExpectedErr | Sepa::ERR_PMT_SEQ_TYPE_MISSING, $oPmtInf->validate());
    
        $oPmtInf->setSeqType('invalid');
        $this->assertEquals($iExpectedErr | Sepa::ERR_PMT_INVALID_SEQ_TYPE, $oPmtInf->validate());
    }
    
    public function testGetCollectionDate()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = new SepaPmtInf($this->oSD);
        // only check for well formed date - for value test see SepaTest::testCalcCollectionDate()
        $this->assertMatchesRegularExpression('/^([0-9]){4}-([0-9]){2}-([0-9]){2}?$/', $oPmtInf->getCollectionDate());
    }
    
    public function testErrorMsg()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = new SepaPmtInf($this->oSD);
        $this->assertNotEmpty($oPmtInf->errorMsg(Sepa::ERR_PMT_IBAN_MISSING));
    }
    
    public function testAddPaymentInstructionInfoError()
    {
        Sepa::Init();
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
        $this->oSD = new SepaDoc(Sepa::CDD);
        $oPmtInf = $this->createValidPmtInf();
        $this->expectError();
        $this->oSD->addPaymentInstructionInfo($oPmtInf);
    }
    
    public function testAddTransactionCDD()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = $this->createValidPmtInf();
        $this->assertEquals(0, $this->oSD->addPaymentInstructionInfo($oPmtInf));
        $this->assertEquals(0, $oPmtInf->addTransaction($this->createValidTxInf(Sepa::CDD)));
        $this->assertEquals(0, $this->oSD->getInvalidCount());
        $this->assertEquals(1, $this->oSD->getTxCount());
        $this->assertEquals(123.45, $this->oSD->getCtrlSum());
        $this->assertEquals(0, $oPmtInf->addTransaction($this->createValidTxInf(Sepa::CDD)));
        $this->assertEquals(2, $this->oSD->getTxCount());
        $this->assertEquals(246.9, $this->oSD->getCtrlSum());
    }
    
    public function testAddTransactionCCT()
    {
        $this->createValidDoc(Sepa::CCT);
        $oPmtInf = $this->createValidPmtInf();
        $this->assertEquals(0, $this->oSD->addPaymentInstructionInfo($oPmtInf));
        $this->assertEquals(0, $oPmtInf->addTransaction($this->createValidTxInf(Sepa::CCT)));
    }
    
    public function testAddTransactionError1()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = $this->createValidPmtInf();
        $this->expectError();
        $oPmtInf->addTransaction($this->createValidTxInf(Sepa::CDD));
    }
    
    public function testAddTransactionError2()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = $this->createValidPmtInf();
        $this->oSD->addPaymentInstructionInfo($oPmtInf);
        $this->assertEquals(Sepa::ERR_TX_INVALID_TYPE, $oPmtInf->addTransaction($this->createValidTxInf(Sepa::CCT)));
    }
    
    public function testAddTransactionError3()
    {
        $this->createValidDoc(Sepa::CDD);
        $oPmtInf = $this->createValidPmtInf();
        $this->oSD->addPaymentInstructionInfo($oPmtInf);
        $oTxInf = $this->createValidTxInf(Sepa::CDD);
        $oTxInf->setBIC('');
        $this->assertEquals(Sepa::ERR_TX_BIC_MISSING, $oPmtInf->addTransaction($oTxInf));
        $this->assertEquals(1, $this->oSD->getInvalidCount());
    }
    
    
    private function createValidDoc($type) : void
    {
        Sepa::Init();
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
        $this->oSD = new SepaDoc($type);
        $this->oSD->createGroupHeader('Test company 4711');
    }
    
    private function createValidTxInf($type) : SepaTxInf
    {
        $oTxInf = new SepaTxInf($type);
        $oTxInf->setIBAN(self::validIBAN);
        $oTxInf->setBIC(self::validBIC);
        $oTxInf->setName('Testname');
        $oTxInf->setDescription('Testdescription');
        $oTxInf->setValue(123.45);
        $oTxInf->setMandateId('MD1234');
        $oTxInf->setDateOfSignature('2019-06-09');
        $oTxInf->setUltimateName('Ultimate Name');
        
        return $oTxInf;
    }
    
    private function createValidPmtInf() : SepaPmtInf
    {
        $oPmtInf = new SepaPmtInf($this->oSD);
        $oPmtInf->setIBAN(self::validIBAN);
        $oPmtInf->setBIC(self::validBIC);
        $oPmtInf->setCI(self::validCI);
        $oPmtInf->setName('Testname');
        $oPmtInf->setSeqType(Sepa::SEQ_FIRST);
        
        return $oPmtInf;
    }
}

