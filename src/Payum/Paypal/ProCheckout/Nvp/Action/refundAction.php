<?php
namespace Payum\Paypal\ProCheckout\Nvp\Action;

use Payum\Core\Action\PaymentAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Exception\LogicException;
use Payum\Core\Request\ObtainCreditCard;
use Payum\Core\Security\SensitiveValue;
use Payum\Paypal\ProCheckout\Nvp\Api;
use Payum\Core\Request\Refund;

class RefundAction extends PaymentAwareAction implements ApiAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request Refund */
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (Api::TRXTYPE_CREDIT == $details['TRXTYPE']) {
            return;
        }

        $cardFields = array('ACCT', 'CVV2', 'EXPDATE');
        if (false == $details->validateNotEmpty($cardFields, false)) {
            try {
                $this->payment->execute($obtainCreditCard = new ObtainCreditCard);

                $card = $obtainCreditCard->obtain();

                $model['EXPDATE'] = new SensitiveValue($card->getExpireAt()->format('my'));
                $model['ACCT'] = new SensitiveValue($card->getNumber());
                $model['CVV2'] = new SensitiveValue($card->getSecurityCode());
            } catch (RequestNotSupportedException $e) {
                throw new LogicException('Credit card details has to be set explicitly or there has to be an action that supports ObtainCreditCard request.');
            }
        }

        $details['RESULT'] = null;

        $details->replace($this->api->doCredit($details->toUnsafeArray()));
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