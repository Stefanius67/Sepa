<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\Sepa;
use SKien\Sepa\SepaDoc;

/**
 * SepaDoc test case.
 */
class SepaDocTest extends TestCase
{
    public function test__construct()
    {
        $this->expectError();
        $oSepaDoc = new SepaDoc('invalid');
        $oSepaDoc->createGroupHeader('invalid');
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

    public function test_CalcError()
    {
        $oSepaDoc = new SepaDoc(Sepa::CCT);
        $this->expectError();
        $oSepaDoc->calc(1234.5);
    }
}

