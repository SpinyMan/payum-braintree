<?php

namespace Payum\Braintree\Request;

use Payum\Core\Model\Identity;
use Payum\Core\Request\Generic;

class Purchase extends Generic
{
    public function __construct($model)
    {
        if ($model instanceof Identity) {
            /** @var \Payum\Core\Payum $payum */
            $payum = app('payum');
            $model = $payum->getStorage($model->getClass())->find($model);
        }

        parent::__construct($model);
    }
}
