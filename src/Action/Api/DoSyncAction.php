<?php
namespace Payum\Braintree\Action\Api;

use Braintree\Transaction;
use Braintree\TransactionSearch;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\ArrayObject;
use Payum\Core\Request\Sync;

class DoSyncAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     * @param Sync $request
     * @throws \Payum\Core\Exception\LogicException if the token not set in the instruction.
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = $request->getModel();
        $sale = $model->offsetGet('sale');
        $transaction = $sale['transaction'] ?? ['id' => null];
        if ($transaction instanceof Transaction) {
            $transactionId = $transaction->id;
        } else {
            $transactionId = $transaction['id'];
        }

        if ($transactionId) {
            $requestParams = [
                TransactionSearch::id()->is($transactionId)
            ];

            $transactionResult = $this->api->search($requestParams);

            $request->setModel($transactionResult->firstItem());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Sync && $request->getModel() instanceof ArrayObject;
    }
}
