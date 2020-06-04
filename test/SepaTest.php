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
class SepaTest extends TestCase
{
    public function testInit()
    {
        $strBIC = 'PARBFRPP757';
        $this->expectError();
        Sepa::validateBIC($strBIC);
        Sepa::init();
        $this->assertSame(Sepa::validateBIC($strBIC), 0);
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
    
    // Tests for the static methods of trait SepaHelper
    public function testCreateUID()
    {
        $this->assertSame(preg_match('/^([0-9]){8}-([0-9]){4}-([0-9]){4}-([0-9]){12}?$/', Sepa::createUID()), 0);
    }
    
    public function testReplaceSpecialChars()
    {
        $this->assertSame(Sepa::replaceSpecialChars('äöüßÄÖÜ'), 'aeoeuessAeOeUe');
    }
    
    public function testValidString()
    {
        $this->assertSame(Sepa::validString('abcdefghijklmnopqrstuvwxyz12345678901234', Sepa::MAX35), 'abcdefghijklmnopqrstuvwxyz123456789');
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
}

