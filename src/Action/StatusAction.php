<?php

namespace Payum\Braintree\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    /**
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var ArrayObject $details */
        $details = /*ArrayObject::ensureArrayObject*/
            ($request->getModel());

        if ($details->offsetExists('status')) {
            $status = $details->offsetGet('status');
            switch ($status) {
                case GetHumanStatus::STATUS_FAILED:
                    $request->markFailed();

                    return;

                case GetHumanStatus::STATUS_AUTHORIZED:
                    if ($this->hasSuccessfulTransaction($details)) {
                        $request->markAuthorized();
                    } else {
                        $request->markUnknown();
                    }

                    return;

                case 'captured':
                    if ($this->hasSuccessfulTransaction($details)) {
                        $request->markCaptured();
                    } else {
                        $request->markUnknown();
                    }

                    return;

                case 'refunded':
                    if ($this->hasSuccessfulTransaction($details)) {
                        $request->markRefunded();
                    } else {
                        $request->markUnknown();
                    }

                    return;
            }
        }

        if ($details->offsetExists('paymentMethodNonce') && $details->offsetGet('paymentMethodNonce')) {
            $request->markPending();

            return;
        }

        $request->markNew();
    }

    protected function hasSuccessfulTransaction(\ArrayAccess $details): bool
    {
        return $details->offsetExists('sale') && $details['sale']['success'];
    }

    public function supports($request)
    {
        return $request instanceof GetStatusInterface && $request->getModel() instanceof \ArrayAccess;
    }
}
