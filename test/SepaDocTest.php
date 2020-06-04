<?php
require_once __DIR__ . '/../autoloader.php';

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\SepaDoc;

/**
 * oValidation test case.
 */
class SepaDocTest extends TestCase
{
    public function test__construct()
    {
        $this->expectError();
        $oSepaDoc = new SepaDoc('invalid');
    }
    
    public function testCreateGroupHeader()
    {
        $oSepaDoc = new SepaDoc(Sepa::CCT);
        $this->assertSame($oSepaDoc->createGroupHeader('Test company 4711'), $oSepaDoc->getId());
    }
    
    public function test_Calc()
    {
        $oSepaDoc = new SepaDoc(Sepa::CCT);
        $oSepaDoc->createGroupHeader('Test company 4711');
        
        $this->assertSame($oSepaDoc->getCtrlSum(), 0.0);
        $oSepaDoc->calc(1234.5);
        $oSepaDoc->calc(1234.5);
        $this->assertSame($oSepaDoc->getCtrlSum(), 2469.0);
        $this->assertSame($oSepaDoc->getTxCount(), 2);
        $this->assertSame($oSepaDoc->getType(), Sepa::CCT);
    }
}

