<?php

namespace Payum\Braintree\Action;

use Payum\Braintree\Request\Purchase;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Braintree\Transaction;
use Payum\Core\Model\Identity;
use Payum\Core\Request\Capture;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute(new Purchase($request->getModel()));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return $request instanceof Capture && $request->getModel() instanceof Identity;
    }
}
