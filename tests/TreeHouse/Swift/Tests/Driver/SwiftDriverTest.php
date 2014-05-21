<?php

namespace TreeHouse\Swift\Tests\Swift\Driver;

use TreeHouse\Keystone\Client\Client;
use TreeHouse\Swift\Driver\SwiftDriver;

class SwiftDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $driver = new SwiftDriver($this->getKeystoneClientMock(), true);

        $this->assertInstanceOf(SwiftDriver::class, $driver);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Client
     */
    protected function getKeystoneClientMock()
    {
        return $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
}
