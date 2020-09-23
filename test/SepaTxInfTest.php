<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\SepaTxInf;

/**
 * oValidation test case.
 */
class SepaTxInfTest extends TestCase
{
    const validIBAN = 'DE11 6829 0000 0009 2158 08';
    const validBIC = 'GENODE61LAH';
    const invalidIBAN = 'DE21 6829 0000 0009 2158 08';
    const invalidBIC = 'GEN0DE61LAH';
    
    public function test__construct()
    {
        $oTxInf = new SepaTxInf(Sepa::CCT);
        $this->assertSame(Sepa::CCT, $oTxInf->getType());
        $oTxInf = new SepaTxInf(Sepa::CDD);
        $this->assertSame(Sepa::CDD, $oTxInf->getType());
    }
    
    public function test__constructError()
    {
        $this->expectError();
        $oTxInf = new SepaTxInf('invalid');
        $oTxInf->getType();
    }
    
    public function testFromArray()
    {
        $aValidTransaction = array(
            'dblValue' => 104.45,
            'strDescription' => 'Test Betreff 1',
            'strName' => 'Mustermann, Max',
            'strIBAN' => self::validIBAN,
            'strBIC' => self::validBIC,
            'strMandateId' => 'ID-0815',
            'strDateOfSignature' => '2018-04-03',
            'strUltimateName' => 'Ultimate Name',
            'strPaymentId' => 'PmtID'
        );
        $oTxInf = new SepaTxInf(Sepa::CDD);
        $oTxInf->fromArray($aValidTransaction);
        $this->assertEquals(self::validIBAN, $oTxInf->getIBAN());
        $this->assertEquals(self::validBIC, $oTxInf->getBIC());
        $this->assertEquals('Mustermann, Max', $oTxInf->getName());
        $this->assertEquals('Test Betreff 1', $oTxInf->getDescription());
        $this->assertEquals(104.45, $oTxInf->getValue());
        $this->assertEquals('ID-0815', $oTxInf->getMandateId());
        $this->assertEquals('Ultimate Name', $oTxInf->getUltimateName());
        $this->assertEquals('PmtID', $oTxInf->getPaymentId());
        $this->assertEquals('2018-04-03', $oTxInf->getDateOfSignature());
    }
    
    public function testSetDateOfSignature()
    {
        $oDT = new DateTime();
        $oDT->setDate(2020, 10, 23);
        $oTxInf = new SepaTxInf(Sepa::CDD);
        $oTxInf->setDateOfSignature($oDT);
        $this->assertEquals('2020-10-23', $oTxInf->getDateOfSignature());
        
        $uxdt = mktime(0, 0, 0, 9, 15, 2019);
        $oTxInf->setDateOfSignature($uxdt);
        $this->assertEquals('2019-09-15', $oTxInf->getDateOfSignature());
    }
    
    public function testValidate()
    {
        $oTxInf = new SepaTxInf(Sepa::CDD);
        $oTxInf->setIBAN(self::validIBAN);
        $oTxInf->setBIC(self::validBIC);
        $oTxInf->setName('Testname');
        $oTxInf->setDescription('Testdescription');
        $oTxInf->setValue(123.45);
        $oTxInf->setMandateId('MD1234');
        $oTxInf->setDateOfSignature('2019-06-09');
        
        $this->assertEquals(0, $oTxInf->validate());
        
        $oTxInf->setPaymentId('');
        $this->assertEquals(0, $oTxInf->validate());
    }
    
    public function testValidateInvalid()
    {
        $oTxInf = new SepaTxInf(Sepa::CDD);
        $oTxInf->setIBAN(self::invalidIBAN);
        $oTxInf->setBIC(self::invalidBIC);
        $oTxInf->setName('Testname');
        $oTxInf->setDescription('Testdescription');
        $oTxInf->setValue(123.45);
        $oTxInf->setMandateId('MD1234');
        $oTxInf->setDateOfSignature('201-06-09');
        $iExpectedErr = Sepa::ERR_TX_INVALID_IBAN | Sepa::ERR_TX_INVALID_BIC | Sepa::ERR_TX_INVALID_MAND_DOS;
        $this->assertEquals($iExpectedErr, $oTxInf->validate());
        
        Sepa::setValidationLevel(Sepa::V_NO_IBAN_VALIDATION | Sepa::V_NO_BIC_VALIDATION);
        $this->assertEquals(Sepa::ERR_TX_INVALID_MAND_DOS, $oTxInf->validate());
        
        Sepa::setValidationLevel(Sepa::V_NO_VALIDATION);
        $this->assertEquals(0, $oTxInf->validate());
    }
    
    public function testValidateMissing()
    {
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
        $oTxInf = new SepaTxInf(Sepa::CDD);
        $iExpectedErr = 
            Sepa::ERR_TX_IBAN_MISSING | 
            Sepa::ERR_TX_BIC_MISSING |
            Sepa::ERR_TX_NAME_MISSING |
            Sepa::ERR_TX_DESCR_MISSING |
            Sepa::ERR_TX_ZERO_VALUE |
            Sepa::ERR_TX_MAND_ID_MISSING |
            Sepa::ERR_TX_MAND_DOS_MISSING;
            
        $this->assertEquals($iExpectedErr, $oTxInf->validate());
    }
    
    public function testErrorMsg()
    {
        $oTxInf = new SepaTxInf(Sepa::CDD);
        $this->assertNotEmpty($oTxInf->errorMsg(Sepa::ERR_TX_IBAN_MISSING));
    } 
}

