<?php
namespace Payum\Paypal\ExpressCheckout\Nvp\Action\Api;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\GetTransactionDetails;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\RefundTransaction;

class RefundTransactionAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param RefundTransaction $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model->validateNotEmpty(array('TRANSACTIONID'));

        $model->replace($this->api->refundTransaction($model->toUnsafeArray()));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return 
            $request instanceof RefundTransaction &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}