<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\SepaDoc;
use SKien\Sepa\SepaPmtInf;
use SKien\Sepa\SepaTxInf;

/**
 * Sepa test case.
 */
class SepaTest extends TestCase
{
    public function testInit()
    {
        Sepa::reset();
        $strBIC = 'PARBFRPP757';
        $this->expectError();
        Sepa::validateBIC($strBIC);
    }

    public function test_NotSupportedVersion()
    {
        $this->expectError();
        Sepa::getPainVersion(Sepa::CDD, '2.0');
    }

    public function testSetValidationLevel()
    {
        Sepa::init();
        $strBIC = 'PARBFRPP757';
        $this->assertSame(Sepa::validateBIC($strBIC), 0);
        $strBIC = 'PARBUSPP757';
        $this->assertSame(Sepa::validateBIC($strBIC), Sepa::ERR_BIC_INVALID_CNTRY);
        Sepa::setValidationLevel(Sepa::V_IGNORE_MISSING_CNTRY);
        $this->assertSame(Sepa::validateBIC($strBIC), 0);
    }

    public function testAddValidationError1()
    {
        // test for error, if validation for country already defined
        Sepa::init();
        $this->expectError();
        Sepa::addValidation('DE', 'newclass');
    }

    public function testAddValidationError2()
    {
        // test for error, if validation class do not implement SepaCntryValidation interface
        Sepa::init();
        $this->expectError();
        Sepa::addValidation('XY', 'DateTime');
    }

    public function testLoadErrorMsgError1()
    {
        // test for error, if error msg file not exist
        $this->expectError();
        Sepa::loadErrorMsg('notexisting.json');
    }

    public function testValidateIBANError1()
    {
        // test for error, if no validation set (Sepa::Init() not called)
        Sepa::reset();
        $strIBAN = 'DE12345678901234567890';
        $this->expectError();
        Sepa::validateIBAN($strIBAN);
    }

    public function testValidateIBANError2()
    {
        // test for OK, if IBAN validation is deactivated
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_NO_IBAN_VALIDATION);
        $strIBAN = 'DE12345678901234567890';
        $this->assertEquals(Sepa::OK, Sepa::validateIBAN($strIBAN));
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
    }

    public function testValidateIBANError3()
    {
        // test for OK, if validation for missing country is deactivated
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_IGNORE_MISSING_CNTRY);
        $strIBAN = 'XY12345678901234567890';
        $this->assertEquals(Sepa::OK, Sepa::validateIBAN($strIBAN));
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
    }

    public function testValidateIBANError4()
    {
        // test for INVALID COUNTRY
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
        $strIBAN = 'XY12345678901234567890';
        $iErr = Sepa::validateIBAN($strIBAN);
        $this->assertEquals(Sepa::ERR_IBAN_INVALID_CNTRY, $iErr);
        $strErr = Sepa::errorMsg($iErr);
        $this->assertFalse(strpos($strErr, 'unbekanter Fehler'));
        $strErr = Sepa::errorMsgIBAN($iErr);
        $this->assertFalse(strpos($strErr, 'unbekanter Fehler'));
    }

    public function testValidateBICError1()
    {
        // test for OK, if IBAN validation is deactivated
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_NO_BIC_VALIDATION);
        $strBIC = 'GENODE1234';
        $this->assertEquals(Sepa::OK, Sepa::validateBIC($strBIC));
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
    }

    public function testValidateBICError2()
    {
        // test for OK, if CI validation is deactivated
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_NO_BIC_VALIDATION);
        $strBIC = 'GENODE1234';
        $this->assertEquals(Sepa::OK, Sepa::validateBIC($strBIC));
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
    }

    public function testValidateBICError3()
    {
        // test for OK, if validation for missing country is deactivated
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_IGNORE_MISSING_CNTRY);
        $strBIC = 'GENOXY1234';
        $this->assertEquals(Sepa::OK, Sepa::validateBIC($strBIC));
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
    }

    public function testValidateBICError4()
    {
        // test for INVALID COUNTRY
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
        $strBIC = 'GENOXY1234';
        $iErr = Sepa::validateBIC($strBIC);
        $this->assertEquals(Sepa::ERR_BIC_INVALID_CNTRY, $iErr);
        $strErr = Sepa::errorMsg($iErr);
        $this->assertFalse(strpos($strErr, 'unbekanter Fehler'));
        $strErr = Sepa::errorMsgBIC($iErr);
        $this->assertFalse(strpos($strErr, 'unbekanter Fehler'));
    }

    public function testValidateCIError1()
    {
        // test for error, if no validation set (Sepa::Init() not called)
        Sepa::reset();
        $strCI = 'DE12345678901234567890';
        $this->expectError();
        Sepa::validateCI($strCI);
    }

    public function testValidateCIError2()
    {
        // test for OK, if CI validation is deactivated
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_NO_CI_VALIDATION);
        $strCI = 'DE12345678901234567890';
        $this->assertEquals(Sepa::OK, Sepa::validateCI($strCI));
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
    }

    public function testValidateCIError3()
    {
        // test for OK, if validation for missing country is deactivated
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_IGNORE_MISSING_CNTRY);
        $strCI = 'XY12345678901234567890';
        $this->assertEquals(Sepa::OK, Sepa::validateCI($strCI));
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
    }

    public function testValidateCIError4()
    {
        // test for INVALID COUNTRY
        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
        $strCI = 'XY12345678901234567890';
        $iErr = Sepa::validateCI($strCI);
        $this->assertEquals(Sepa::ERR_CI_INVALID_CNTRY, $iErr);
        $strErr = Sepa::errorMsg($iErr);
        $this->assertFalse(strpos($strErr, 'unbekanter Fehler'));
        $strErr = Sepa::errorMsgCI($iErr);
        $this->assertFalse(strpos($strErr, 'unbekanter Fehler'));
    }

    // Tests for the static methods of trait SepaHelper
    public function testCreateUID()
    {
        // $this->assertSame(preg_match('/^([0-9]){8}-([0-9]){4}-([0-9]){4}-([0-9]){12}?$/', Sepa::createUID()), 0);
        $this->assertMatchesRegularExpression('/^([0-9A-F]){8}-([0-9A-F]){4}-([0-9A-F]){4}-([0-9A-F]){12}?$/', Sepa::createUID());

    }

    public function testReplaceSpecialChars()
    {
        $this->assertSame(Sepa::replaceSpecialChars('äöüßÄÖÜ'), 'aeoeuessAeOeUe');
    }

    public function testValidString()
    {
        $this->assertSame(Sepa::validString('abcdefghijklmnopqrstuvwxyz12345678901234', Sepa::MAX35), 'abcdefghijklmnopqrstuvwxyz123456789');
        $this->assertSame(Sepa::validString('abcdefghijklmnopqrstuvwxyz12345678901234', Sepa::MAX1025), 'abcdefghijklmnopqrstuvwxyz12345678901234');
    }

    public function testIsTarget2Day()
    {
        $dt = mktime(0, 0, 0, 12, 25, 2024); // 1'st chrismasday
        $this->assertTrue(Sepa::isTarget2Day($dt));
        $dt = mktime(0, 0, 0, 12, 16, 2020); // Wednesday...
        $this->assertFalse(Sepa::isTarget2Day($dt));
        $dt = mktime(0, 0, 0, 6, 21, 2020); // Sunday...
        $this->assertTrue(Sepa::isTarget2Day($dt));
    }

    public function testCalcCollectionDate()
    {
        $dt = mktime(0, 0, 0, 6, 17, 2020); // Wednesday
        $dtCalc = Sepa::calcCollectionDate(5, $dt);
        $this->assertSame($dtCalc, mktime(0, 0, 0, 6, 24, 2020));
        $dtCalc = Sepa::calcCollectionDate(5);
        $this->assertTrue($dtCalc >= mktime() + 5 * 86400);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_Output()
    {
        $oSepaDoc = $this->createValidCDD();

        ob_start();
        $oSepaDoc->output('test.xml');

        /*
        header('Content-Type: application/xml');
        header('Content-Disposition: ' . $strTarget . '; filename="' . $strName . '"');
        header('Pragma: public');
        */
        $aHeaders = xdebug_get_headers();
        $aAssocHeaders = [];
        foreach ($aHeaders as $strHeader) {
            list($strName, $strValue) = explode(':', $strHeader, 2);
            $aAssocHeaders[trim($strName)] = trim($strValue);
        }
        $this->assertArrayHasKey('Content-Type', $aAssocHeaders);
        $this->assertEquals('application/xml', $aAssocHeaders['Content-Type']);
        $this->assertArrayHasKey('Content-Disposition', $aAssocHeaders);
        $this->assertNotFalse(strpos($aAssocHeaders['Content-Disposition'], 'attachment'));
        $this->assertNotFalse(strpos($aAssocHeaders['Content-Disposition'], '"test.xml"'));

        $this->assertArrayHasKey('Pragma', $aAssocHeaders);

        ob_end_clean();
    }

    /**
     * @dataProvider versionProvider
     */
    public function test_validateCDDagainstXSD(string $strVersion)
    {
        $oSepaDoc = $this->createValidCDD($strVersion);
        $strPain = Sepa::getPainVersion(Sepa::CDD, $strVersion);
        $strErrorMsg = $this->validateAgainstXSD($oSepaDoc, $strPain . '.xsd');

        if (strlen($strErrorMsg) > 0) {
            $this->fail($strErrorMsg);
        }
        // valid HTML
        $this->assertTrue(true);
    }

    /**
     * @dataProvider versionProvider
     */
    public function test_validateCCTagainstXSD(string $strVersion)
    {
        $oSepaDoc = $this->createValidCCT($strVersion);
        $strPain = Sepa::getPainVersion(Sepa::CCT, $strVersion);
        $strErrorMsg = $this->validateAgainstXSD($oSepaDoc, $strPain . '.xsd');

        if (strlen($strErrorMsg) > 0) {
            $this->fail($strErrorMsg);
        }
        // valid HTML
        $this->assertTrue(true);
    }

    public function versionProvider() : array
    {
        return [
            Sepa::V26 => [Sepa::V26],
            Sepa::V29 => [Sepa::V29],
            Sepa::V30 => [Sepa::V30],
        ];
    }

    protected function createValidCDD(string $strVersion = Sepa::V30) : SepaDoc
    {
        $aValidTransaction = array(
            'dblValue' => 104.45,
            'strDescription' => 'Test Betreff 1',
            'strName' => 'Mustermann, Max',
            'strIBAN' => 'DE11682900000009215808',
            'strBIC' => 'GENODE61LAH',
            'strMandateId' => 'ID-0815',
            'strDateOfSignature' => '2018-04-03'
        );
        $aValidPPI = array(
            'strName' => 'Testfirma',
            'strCI' => 'CH51 ZZZ 12345678901',
            'strIBAN' => 'DE71664500500070143559',
            'strBIC' => 'GENODE61LAH',
            'strSeqType' => Sepa::SEQ_RECURRENT,
        );

        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);
        Sepa::loadErrorMsg(__DIR__ . '/testdata/sepa_errormsg_de.json');

        // test for dirct debit transdaction
        $type = Sepa::CDD;

        // create new SEPA document with header
        $oSepaDoc = new SepaDoc($type, $strVersion);
        $oSepaDoc->createGroupHeader('Test company 4711');

        // create payment info instruction (PII) and set all needet creditor information
        $oPPI = new SepaPmtInf($oSepaDoc);
        $oPPI->fromArray($aValidPPI);

        // add the PII to the document.
        $this->assertEquals(Sepa::OK, $oSepaDoc->addPaymentInstructionInfo($oPPI));

        $oTxInf = new SepaTxInf($type);
        $oTxInf->fromArray($aValidTransaction);
        $this->assertEquals(Sepa::OK, $oPPI->addTransaction($oTxInf));

        return $oSepaDoc;
    }

    protected function createValidCCT(string $strVersion = Sepa::V30) : SepaDoc
    {
        $aValidTransaction = array(
            'dblValue' => 104.45,
            'strDescription' => 'Test Betreff 1',
            'strName' => 'Mustermann, Max',
            'strIBAN' => 'DE11682900000009215808',
            'strBIC' => 'GENODE61LAH',
            'strMandateId' => 'ID-0815',
            'strDateOfSignature' => '2018-04-03'
        );

        Sepa::init();
        Sepa::setValidationLevel(Sepa::V_FULL_VALIDATION);

        // test for dirct debit transdaction
        $type = Sepa::CCT;

        // create new SEPA document with header
        $oSepaDoc = new SepaDoc($type, $strVersion);
        $oSepaDoc->createGroupHeader('Test company 4711');

        // create payment info instruction (PII) and set all needet creditor information
        $oPPI = new SepaPmtInf($oSepaDoc);
        $oPPI->setName('Testfirma');
        $oPPI->setCI('CH51 ZZZ 12345678901');
        $oPPI->setIBAN('DE71664500500070143559');
        $oPPI->setBIC('GENODE61LAH');
        $oPPI->setSeqType(Sepa::SEQ_RECURRENT);

        // add the PII to the document.
        $this->assertEquals(Sepa::OK, $oSepaDoc->addPaymentInstructionInfo($oPPI));

        $oTxInf = new SepaTxInf($type);
        $oTxInf->fromArray($aValidTransaction);
        $this->assertEquals(Sepa::OK, $oPPI->addTransaction($oTxInf));

        return $oSepaDoc;
    }

    protected function validateAgainstXSD(SepaDoc $oSepaDoc, string $strXSD) : string
    {
        $strErrorMsg = '';

        libxml_use_internal_errors(true);

        /*
         * save the created xml in temp folder and reload
         * -> schemaValidation doesn't work correct on 'memory created' DOM document
         *    see: https://www.php.net/manual/de/domdocument.schemavalidate.php#89893
         */
        $oSepaDoc->save(__DIR__ . '/temp/test.xml');
        $oTempDoc = new \DOMDocument();
        $oTempDoc->load(__DIR__ . '/temp/test.xml');
        if (!$oTempDoc->schemaValidate(__DIR__ . '/testdata/' . $strXSD)) {
            $errors = libxml_get_errors();
            $aLevel = [LIBXML_ERR_WARNING => 'Warning ', LIBXML_ERR_ERROR => 'Error ', LIBXML_ERR_FATAL => 'Fatal Error '];

            $strStart = "Schema validation failed (" . $strXSD . "):";
            foreach ($errors as $error) {
                $strErrorMsg .= $strStart . PHP_EOL . '   ' . $aLevel[$error->level] . $error->code;
                $strErrorMsg .= ' -> (Line ' . $error->line . ', Col ' . $error->column . '): ' . trim($error->message);
                $strStart = '';
            }
        }

        unlink(__DIR__ . '/temp/test.xml');
        libxml_clear_errors();

        return $strErrorMsg;
    }
}

