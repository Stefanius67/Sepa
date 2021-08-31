<?php
declare(strict_types=1);

namespace SKien\Test\Sepa;

use PHPUnit\Framework\TestCase;
use SKien\Sepa\SclDirectory;

/**
 * Sepa test case.
 */
class SclDirectoryTest extends TestCase
{
    const DATAPATH = __DIR__ . DIRECTORY_SEPARATOR . 'testdata';

    protected ?string $strHttpsProxy = null;
    protected ?string $strNoProxy = null;

    protected function tearDown() : void
    {
        if ($this->strHttpsProxy !== null) {
            putenv("https_proxy=" . $this->strHttpsProxy);
        }
        if ($this->strNoProxy !== null) {
            putenv("no_proxy=" . $this->strNoProxy);
        }
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::DATAPATH . DIRECTORY_SEPARATOR . 'scl-directory.xml');
    }

    public function testInit() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH);
        $this->assertTrue($oSCL->Init());
    }

    public function testInitReadonlyDir() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'readonlydir');
        $this->assertFalse($oSCL->Init());
        $this->assertNotEmpty($oSCL->getError());
    }

    public function testInitReadonlyFile() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'readonlyfile');
        $this->assertFalse($oSCL->Init());
        $this->assertNotEmpty($oSCL->getError());
    }

    public function testLoadFromInternetNew() : void
    {
        @unlink(self::DATAPATH . DIRECTORY_SEPARATOR . 'scl-directory.xml');
        $oSCL = new SclDirectory(self::DATAPATH);
        $oSCL->Init();
        $this->assertTrue($oSCL->loadFromInternet('PT5S'));
    }

    public function testLoadFromInternetExpired1() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertTrue($oSCL->loadFromInternet('PT5S'));
    }

    public function testLoadFromInternetExpired2() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertTrue($oSCL->loadFromInternet(5));
    }

    public function testLoadFromInternetNotExpired1() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertTrue($oSCL->loadFromInternet('P10Y'));
    }

    public function testLoadFromInternetNotExpired2() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertTrue($oSCL->loadFromInternet(300000000));
    }

    public function testLoadFromInternetDateIntervalFailed() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertFalse($oSCL->loadFromInternet('bad'));
        $this->assertStringContainsString('DateInterval::__construct()', $oSCL->getError());
    }

    public function testLoadFromInternetCurlFail() : void
    {
        // force cURL error!
        $this->strHttpsProxy = (getenv("https_proxy") !== false ? getenv("https_proxy") : '');
        $this->strNoProxy =  (getenv("no_proxy") !== false ? getenv("no_proxy") : '');
        putenv("https_proxy=localhost:5678");
        putenv("no_proxy=blah-blah-blah");

        $oSCL = new SclDirectory(self::DATAPATH);
        $oSCL->Init();
        $this->assertFalse($oSCL->loadFromInternet());
        $this->assertStringContainsString('cURL-Error:', $oSCL->getError());
    }

    public function testIsValidBIC1() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertTrue($oSCL->isValidBIC('BBVABEBB'));
    }

    public function testIsValidBIC2() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertTrue($oSCL->isValidBIC('BIBLBE21XXX'));
    }

    public function testIsValidBIC3() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertFalse($oSCL->isValidBIC('Invalid'));
    }

    public function testGetNameFromBIC1() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertNotEmpty($oSCL->getNameFromBIC('BBVABEBB'));
    }

    public function testGetNameFromBIC2() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertNotEmpty($oSCL->getNameFromBIC('BIBLBE21XXX', true));
    }

    public function testGetNameFromBIC3() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertNotEmpty($oSCL->getNameFromBIC('CUABDED1', true));
    }

    public function testGetNameFromBIC4() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $this->assertEmpty($oSCL->getNameFromBIC('Invalid'));
    }

    public function testGetProviderList1() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $aList = $oSCL->getProviderList('EE');
        $this->assertGreaterThan(0, count($aList));
    }

    public function testGetProviderList2() : void
    {
        $oSCL = new SclDirectory(self::DATAPATH . DIRECTORY_SEPARATOR . 'expired');
        $oSCL->Init();
        $aList = $oSCL->getProviderList();
        $this->assertGreaterThan(0, count($aList));
    }
}

