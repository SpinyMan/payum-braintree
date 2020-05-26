<?php

namespace Payum\Braintree\Tests\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Security\TokenInterface;
use PHPUnit\Framework\TestCase;

abstract class GenericActionTest extends TestCase
{
    /**
     * @var Generic
     */
    protected $requestClass;

    /**
     * @var string
     */
    protected $actionClass;

    /**
     * @var ActionInterface
     */
    protected $action;

    protected function setUp()
    {
        $this->action = new $this->actionClass();
    }

    public function provideSupportedRequests()
    {
        return [
            [new $this->requestClass([])],
            [new $this->requestClass(new \ArrayObject())],
        ];
    }

    public function provideNotSupportedRequests()
    {
        return [
            ['foo'],
            [['foo']],
            [new \stdClass()],
            [new $this->requestClass('foo')],
            [new $this->requestClass(new \stdClass())],
            [$this->getMockForAbstractClass(Generic::class, [[]])],
        ];
    }

    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);

        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }

    protected function createTokenMock()
    {
        return $this->createMock(TokenInterface::class);
    }
}
