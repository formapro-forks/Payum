<?php
namespace Payum\Paypal\ExpressCheckout\Nvp\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Refund;
use Payum\Core\Request\Sync;
use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\RefundTransaction;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\SetExpressCheckout;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\AuthorizeToken;
use Payum\Paypal\ExpressCheckout\Nvp\Request\Api\DoExpressCheckoutPayment;
use Payum\Paypal\ExpressCheckout\Nvp\Api;

class RefundAction extends PaymentAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @parma Refund $request
     */
    public function execute($request)
    {
        /** @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
//REFUNDTYPE
        if ($model['TRANSACTIONID']) {
            $this->payment->execute(new RefundTransaction($model));

            return;
        }

        foreach (range(0, 9) as $index) {
            if ($model['PAYMENTREQUEST_'.$index.'_TRANSACTIONID']) {
                $this->payment->execute($refundTransaction = new RefundTransaction(array(
                    'TRANSACTIONID' => $model['PAYMENTREQUEST_'.$index.'_TRANSACTIONID'],
            )));
            }
        }

        $this->payment->execute(new Sync($model));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess
        ; 
    }
}
