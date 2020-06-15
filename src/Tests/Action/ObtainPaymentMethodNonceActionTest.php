<?php

namespace Payum\Braintree\Tests\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\GatewayInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Braintree\Action\ObtainPaymentMethodNonceAction;
use Payum\Braintree\Request\ObtainPaymentMethodNonce;
use Payum\Braintree\Request\Api\GenerateClientToken;
use PHPUnit\Framework\TestCase;

class ObtainPaymentMethodNonceActionTest extends TestCase
{
    protected $action;

    public function setUp()
    {
        $config = new ArrayObject();
        $config->offsetSet('payum.template.obtain_payment_method_nonce', 'aTemplateName');
        $this->action = new ObtainPaymentMethodNonceAction($config);
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(ObtainPaymentMethodNonceAction::class);
        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    public function provideSupportedRequests()
    {
        return [
            [new ObtainPaymentMethodNonce([])],
            [new ObtainPaymentMethodNonce(new \ArrayObject())],
        ];
    }

    public function provideNotSupportedRequests()
    {
        return [
            ['foo'],
            [['foo']],
            [new \stdClass()],
            [new ObtainPaymentMethodNonce('foo')],
            [new ObtainPaymentMethodNonce(new \stdClass())],
            [$this->getMockForAbstractClass(Generic::class, [[]])],
        ];
    }

    /**
     * @test
     */
    public function shouldThrowHttpResponseIfHttpRequestNotPost()
    {
        $gatewayMock = $this->createGatewayMock();

        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will(
                $this->returnCallback(
                    function (GetHttpRequest $request) {
                        $request->method = 'GET';
                    }
                )
            );

        $gatewayMock
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->isInstanceOf(GenerateClientToken::class))
            ->will(
                $this->returnCallback(
                    function (GenerateClientToken $request) {
                        $request->setResponse('aClientToken');
                    }
                )
            );

        $gatewayMock
            ->expects($this->at(2))
            ->method('execute')
            ->with($this->isInstanceOf(RenderTemplate::class))
            ->will(
                $this->returnCallback(
                    function (RenderTemplate $request) {
                        $this->assertEquals('aTemplateName', $request->getTemplateName());

                        $templateParameters = $request->getParameters();

                        $this->assertEquals('aClientToken', $templateParameters['clientToken']);
                        $this->assertEquals(123, $templateParameters['amount']);
                        $this->assertEquals(false, $templateParameters['obtainCardholderAuthentication']);

                        $request->setResult('renderedTemplate');
                    }
                )
            );

        $this->action->setGateway($gatewayMock);

        $this->action->setCardholderAuthenticationRequired(false);

        try {
            $this->action->execute(
                new ObtainPaymentMethodNonce(
                    [
                        'amount' => 123,
                    ]
                )
            );
        } catch (HttpResponse $reply) {
            $this->assertEquals('renderedTemplate', $reply->getContent());

            return;
        }

        $this->fail('HttpResponse reply was expected to be thrown.');
    }

    /**
     * @test
     */
    public function shouldSetResponseIfHttpRequestPost()
    {
        $gatewayMock = $this->createGatewayMock();

        $gatewayMock
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->will(
                $this->returnCallback(
                    function (GetHttpRequest $request) {
                        $request->method                          = 'POST';
                        $request->request['payment_method_nonce'] = 'aPaymentMethodNonce';
                    }
                )
            );

        $this->action->setGateway($gatewayMock);

        $this->action->execute($request = new ObtainPaymentMethodNonce([]));

        $this->assertEquals('aPaymentMethodNonce', $request->getResponse());
    }

    /**
     * @test
     */
    public function shouldNotOperateIfPaymentMethodNoncePresent()
    {
        $gatewayMock = $this->createGatewayMock();

        $gatewayMock
            ->expects($this->never())
            ->method('execute');

        $this->action->setGateway($gatewayMock);

        $this->action->execute(
            $request = new ObtainPaymentMethodNonce(
                [
                    'paymentMethodNonce' => 'first_nonce',
                ]
            )
        );

        $this->assertEquals('first_nonce', $request->getResponse());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
