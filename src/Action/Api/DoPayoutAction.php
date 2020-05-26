<?php

namespace Payum\Braintree\Action\Api;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Request\Payout;

class DoPayoutAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = $request->getModel();
        $request->setModel(
            $this->api->submitForSettlement(
                $model->offsetGet('transactionId'),
                $model->offsetGet('amount')
            )
        );
    }

    public function supports($request)
    {
        return $request instanceof Payout && $request->getModel() instanceof ArrayObject;
    }
}
