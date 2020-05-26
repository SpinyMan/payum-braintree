<?php

namespace Payum\Braintree\Action\Api;

use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Request\Cancel;

class DoCancelAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = $request->getModel();
        $request->setModel(
            $this->api->void(
                $model->offsetGet('transactionId')
            )
        );
    }

    public function supports($request)
    {
        return $request instanceof Cancel && $request->getModel() instanceof ArrayObject;
    }
}
