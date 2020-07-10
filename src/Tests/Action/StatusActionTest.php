<?php

namespace Payum\Braintree\Tests\Action;

use Braintree\Transaction;
use Payum\Core\Request\GetHumanStatus;
use Payum\Braintree\Action\StatusAction;

class StatusActionTest extends GenericActionTest
{
    protected $actionClass  = StatusAction::class;

    protected $requestClass = GetHumanStatus::class;

    public function testShouldMarkNewIfDetailsEmpty()
    {
        $action = new StatusAction();

        $action->execute($status = new GetHumanStatus([]));

        $this->assertTrue($status->isNew());
    }

    public function testShouldMarkPendingIfOnlyHasPaymentMethodNonce()
    {
        $action = new StatusAction();

        $action->execute(
            $status = new GetHumanStatus(
                [
                    'paymentMethodNonce' => '1234',
                ]
            )
        );

        $this->assertTrue($status->isPending());
    }

    public function testShouldMarkFailedIfHasFailedStatus()
    {
        $action = new StatusAction();

        $action->execute(
            $status = new GetHumanStatus(
                [
                    'status' => GetHumanStatus::STATUS_FAILED,
                ]
            )
        );

        $this->assertTrue($status->isFailed());
    }

    public function testShouldMarkAuthorized()
    {
        $action = new StatusAction();

        $action->execute(
            $status = new GetHumanStatus(
                [
                    'status' => GetHumanStatus::STATUS_AUTHORIZED,
                    'sale'   => [
                        'success' => true,
                    ],
                ]
            )
        );

        $this->assertTrue($status->isAuthorized());
    }

    public function testShouldMarkCaptured()
    {
        $action = new StatusAction();

        $action->execute(
            $status = new GetHumanStatus(
                [
                    'status' => GetHumanStatus::STATUS_CAPTURED,
                    'sale'   => [
                        'success' => true,
                    ],
                ]
            )
        );

        $this->assertTrue($status->isCaptured());
    }

    public function testShouldMarkUnknownIfMissingTransactionSuccess()
    {
        $action = new StatusAction();

        $action->execute(
            $status = new GetHumanStatus(
                [
                    'status' => GetHumanStatus::STATUS_CAPTURED,
                ]
            )
        );

        $this->assertTrue($status->isUnknown());
    }

    public function testShouldMarkUnknownIfTransactionFalseSuccess()
    {
        $action = new StatusAction();

        $action->execute(
            $status = new GetHumanStatus(
                [
                    'status' => GetHumanStatus::STATUS_CAPTURED,
                    'sale'   => [
                        'success' => false,
                    ],
                ]
            )
        );

        $this->assertTrue($status->isUnknown());
    }
}
