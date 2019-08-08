<?php
namespace Payum\Braintree\Action\Api;

use App\Modules\Payment\Models\SyncDTO;
use Braintree\Transaction;
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
        $transactionId = array_get($sale, 'transaction.id');
        $requestParams = [
            \Braintree\TransactionSearch::id()->is($transactionId)
        ];

        $transactionResult = $this->api->search($requestParams);

        $request->setModel($transactionResult->firstItem());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof Sync && $request->getModel() instanceof ArrayObject;
    }
}
