<?php
namespace Payum\Braintree\Action\Api;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Braintree\Request\Api\DoSale;

class DoSaleAction extends BaseApiAwareAction
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

        $requestParams = $this->getSaleRequestParams($request);

        $transactionResult = $this->api->sale($requestParams);

        $request->setResponse($transactionResult);
    }

    private function getSaleRequestParams($request)
    {
        $details = /*ArrayObject::ensureArrayObject*/($request->getModel());

        if ( ! $details->offsetExists('amount')) {
            throw new \Exception('Validation error');
        }

        $requestParams = new ArrayObject();

        $forwardParams = [
            'amount', 
            'paymentMethodNonce',
            'paymentMethodToken',
            'creditCard',
            'billing',
            'shipping',
            'customer',
            'customerId',
            'orderId',
        ];

        foreach($forwardParams as $paramName) {
            if ($details->offsetExists($paramName)) {
                $requestParams[$paramName] = $details[$paramName];
            }
        }

        if ($details->offsetExists('saleOptions')) {
            $requestParams['options'] = $details['saleOptions'];
        }

        return $requestParams;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof DoSale &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
