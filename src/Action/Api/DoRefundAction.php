<?php
namespace Payum\Braintree\Action\Api;

use Payum\Core\Request\Refund;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Braintree\Request\Api\DoSale;

class DoRefundAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @throws \Payum\Core\Exception\LogicException if the token not set in the instruction.
     */
    public function execute($request)
    {
        /** @var $request DoSale */
        RequestNotSupportedException::assertSupports($this, $request);

        $requestParams = $this->getRefundRequestParams($request);

        $transactionResult = $this->api->refund($requestParams);

        $request->setResponse($transactionResult);
    }

    private function getRefundRequestParams($request)
    {
        $details = /*ArrayObject::ensureArrayObject*/($request->getModel());

        if (! $details->offsetExists('amount') || ! $details->offsetExists('transactionId')) {
            throw new \Exception('Validation error');
        }

        $params = new ArrayObject();
        foreach (['amount', 'transactionId'] as $key)  {
            $params->offsetSet($key, $details->offsetGet($key));
        }

        return $params;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Refund && $request->getModel() instanceof \ArrayAccess;
    }
}
