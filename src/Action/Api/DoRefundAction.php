<?php

namespace Payum\Braintree\Action\Api;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Request\Refund;

class DoRefundAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        /** @var Refund $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $requestParams = $this->getRefundRequestParams($request);

        $transactionResult = $this->api->refund($requestParams);

        $request->setModel($transactionResult);
    }

    private function getRefundRequestParams($request)
    {
        $details = /*ArrayObject::ensureArrayObject*/
            ($request->getModel());

        if (!$details->offsetExists('amount') || !$details->offsetExists('transactionId')) {
            throw new \RuntimeException('Validation error');
        }

        $params = new ArrayObject();
        foreach (['amount', 'transactionId'] as $key) {
            $params->offsetSet($key, $details->offsetGet($key));
        }

        return $params;
    }

    public function supports($request)
    {
        return $request instanceof Refund && $request->getModel() instanceof \ArrayAccess;
    }
}
