<?php
namespace Payum\Paypal\ExpressCheckout\Nvp;

use Buzz\Client\ClientInterface;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\RuntimeException;

class APApi
{
    const CMD_ADAPTIVE_PAYMENT = '_ap-payment';
    
    const CMD_ADAPTIVE_PREAPPROVAL = '_ap-preapproval';

    /**
     * All Adaptive API calls in sandbox mode should have this
     */
    const APP_ID = 'APP-80W284485P519543T';

    protected $client;

    protected $options = array(
        'username' => null,
        'password' => null,
        'signature' => null,
        'subject' => null,
        'appid' => null,
        'sandbox' => null
    );

    /**
     * @param array $options
     * @param ClientInterface|null $client
     */
    public function __construct(array $options, ClientInterface $client = null)
    {
        $this->client = $client ?: new Curl;

        $this->options = array_replace($this->options, $options);

        if (true == empty($this->options['username'])) {
            throw new InvalidArgumentException('The username option must be set.');
        }
        if (true == empty($this->options['password'])) {
            throw new InvalidArgumentException('The password option must be set.');
        }
        if (true == empty($this->options['subject'])) {
            throw new InvalidArgumentException('The subject option must be set.');
        }
        if (true == empty($this->options['signature'])) {
            throw new InvalidArgumentException('The signature option must be set.');
        }
        if (true == empty($this->options['appip']) && !$this->options['sandbox']) {
            throw new InvalidArgumentException('The appid option must be set (except when sandbox=true).');
        }else{
            $this->options['appid'] = APApi::APP_ID;
        }
        if (false == is_bool($this->options['sandbox'])) {
            throw new InvalidArgumentException('The boolean sandbox option must be set.');
        }
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function pay(array $fields)
    {
        $request = new FormRequest;
        $request->setFields($fields);

        $request->setField('METHOD', 'Pay');
        $request->addHeader("");

        $this->addAuthorizeFields($request);
        $this->addAppID($request);

        return $this->doRequest($request);
    }

    /**
     * @param FormRequest $request
     *
     * @throws HttpException
     *
     * @return array
     */
    protected function doRequest(FormRequest $request)
    {
        $request->setMethod('POST');
        $request->fromUrl($this->getApiEndpoint());

        $this->client->send($request, $response = new Response);

        if (false == $response->isSuccessful()) {
            throw HttpException::factory($request, $response);
        }

        $result = array();
        parse_str($response->getContent(), $result);
        foreach ($result as &$value) {
            $value = urldecode($value);
        }

        return $result;
    }

    //TODO

    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        return $this->options['sandbox'] ?
            'https://svcs.sandbox.paypal.com/AdaptivePayments/API_operation' :
            'https://svcs.paypal.com/AdaptivePayments/API_operation'
        ;
    }

    /**
     * @param FormRequest $request
     */
    protected function addAuthorizeFields(FormRequest $request)
    {
        $request->addHeader('X-PAYPAL-SECURITY-PASSWORD: ' . $this->options['password']);
        $request->addHeader('X-PAYPAL-SECURITY-USERID: ' . $this->options['username']);
        $request->addHeader('X-PAYPAL-SECURITY-SIGNATURE: ' . $this->options['signature']);
        $request->addHeader('X-PAYPAL-SECURITY-SUBJECT: ' . $this->options['subject']);
    }

    protected function addAppID(FormRequest $request)
    {
        $request->addHeader('X-PAYPAL-APPLICATION-ID: ' . $this->options['appid']);
    }
}