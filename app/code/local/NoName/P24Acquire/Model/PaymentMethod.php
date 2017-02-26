<?php

/**
 * P24Acquire Payment Model
 *
 * @category   NoName
 * @package    NoName_P24Acquire
 * @author     https://github.com/evgv
 * @version    1.0.0
 */

class NoName_P24Acquire_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    const STATUS_SUCCESS     = 'ok';
    const STATUS_FAILURE     = 'failure';
    const STATUS_WAIT_SECURE = 'wait_secure';
    const STATUS_WAIT_ACCEPT = 'wait_accept';
    const STATUS_SANDBOX     = 'test';
    
    const XML_PATH_ROUND           = 'payment/p24acquire/round';
    const XML_PATH_ROUND_PRECISION = 'payment/p24acquire/round_precision';
    
    const XML_GENERAL_STORE_NAME   = 'general/store_information/name';
    
    const REIRECT_URL = 'p24acquire/payment/redirect';
    const PAY_WAY     = 'privat24';

    /**
     * Payment Method features
     * 
     * @var bool
     */
    protected $_canCapture              = true;
    protected $_canVoid                 = true;
    protected $_canUseForMultishipping  = false;
    protected $_canUseInternal          = false;
    protected $_isInitializeNeeded      = true;
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canUseCheckout          = true;

    protected $_code              = 'p24acquire';
    protected $_formBlockType     = 'p24acquire/paymentInformation';
    protected $_allowCurrencyCode = array('EUR','UAH','USD','RUB', 'RUR');
    protected $_order;


   /**
    * Retrieve needed fields
    *
    * @return array
    */
    public function getRedirectFormFields()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

        if (!$order->getId()) {
            return array();
        }

        $merchant_id  = $this->getConfigData('merchant_id');
        
        $amount = Mage::helper('directory')->currencyConvert(
                    $order->getSubtotal(), 
                    Mage::app()->getStore()->getCurrentCurrencyCode(), 
                    $order->getOrderCurrencyCode()
                );
        
        if(Mage::getStoreConfig(self::XML_PATH_ROUND, Mage::app()->getStore()->getId())){
            $amount = $this->roundTotals($amount);
        }
        
        $currency = $order->getOrderCurrencyCode();
        
        if ($currency == 'RUR') {
            $currency = 'RUB';
        }

        $order_id     = $order->getIncrementId();
        $details      = Mage::helper('p24acquire')->__('Payment for products in store "%s"', Mage::getStoreConfig(self::XML_GENERAL_STORE_NAME));
        $ext_details  = Mage::helper('p24acquire')->__('Order № %s', $order_id);
        $pay_way      = self::PAY_WAY;
        $return_url   = Mage::getUrl('p24acquire/payment/result');
        $server_url   = Mage::getUrl('p24acquire/payment/server');

        $request = array(
            'amt'           => $amount,
            'ccy'           => $currency,
            'merchant'      => $merchant_id,
            'order'         => $order_id,
            'details'       => $details,
            'ext_details'   => $ext_details,
            'pay_way'       => $pay_way,
            'return_url'    => $return_url,
            'server_url'    => $server_url
        );

        $this->_debug(array(
            'url'     => $this->getConfigPaymentAction(),
            'request' => $request
        ));
        
        return $request;
    }
    
    /**
    * Get redirect url.
    * Return Order place redirect url.
    *
    * @return string
    */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl(self::REIRECT_URL, array('_secure' => true));
    }


    /**
     * Return Privat24 place URL
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('action');
    }


    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage::getSingleton('sales/order_config')->getStateDefaultStatus($state));
        $stateObject->setIsNotified(false);
        
        return $this;
    }


    /**
     * Validate data from Privat24 server and update the database
     *
     * @var array $post
     *
     * @return void
     */
    public function processNotification($post)
    {
        $this->_debug(array(
            'response' => $post
        ));

        $success = isset($post['payment']) && isset($post['signature']);

        if (!$success) {
            Mage::throwException(Mage::helper('p24acquire')->__('Payment or signature is empty'));
        }
        
        $payment     = $post['payment'];
        $parsed_data = $this->parseDate($payment);
        
        $this->_debug(array(
            'parsed_response' => $parsed_data
        ));

        $received_signature   = $post['signature'];
        $received_merchant_id = $parsed_data['merchant'];
        
        $amount   = $parsed_data['amt'];
        $order_id = $parsed_data['order'];
        $state    = $parsed_data['state'];
        $currency = $parsed_data['ccy'];
        
        Mage::log($state, null, __METHOD__.'__PAYMENT_STATE.log');
        
        if(Mage::getStoreConfig(self::XML_PATH_ROUND)){
            $amount = $this->roundTotals($amount);
        }

        if ($order_id <= 0) {
            Mage::throwException(Mage::helper('p24acquire')->__('Order id is not set'));
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($order_id);

        if (!$order->getId()) {
            Mage::throwException(Mage::helper('p24acquire')->__('Cannot load order'));
        }

        $merchant_id  = $this->getConfigData('merchant_id');
        $merchant_key = $this->getConfigData('secret_key');

        $generated_signature = sha1(md5($payment . $merchant_key));

        if ($received_signature != $generated_signature || $merchant_id != $received_merchant_id) {
            $order->addStatusHistoryComment(Mage::helper('p24acquire')->__('Security check failed!'));
            $order->save();
            return;
        }

        $newOrderStatus = $this->getConfigData('order_status', $order->getStoreId());
        if (empty($newOrderStatus)) {
            $newOrderStatus = $order->getStatus();
        }

        switch ($state) {
            case self::STATUS_SANDBOX:
            case self::STATUS_SUCCESS:
                if ($order->canInvoice()) {
                    $invoice = $order->prepareInvoice();
                    $invoice->register()->pay();
                    Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();

                    $message = Mage::helper('p24acquire')->__(
                        'Invoice #%s created.',
                        $invoice->getIncrementId()
                    );

                    $order->setState(
                        Mage_Sales_Model_Order::STATE_PROCESSING, true,
                        $message,
                        $notified = true
                    );

                    $sDescription = Mage::helper('p24acquire')->__(
                                        'Payed by Privat24 service, amout: %s %s', 
                                        $amount, 
                                        $currency
                                    );

                    $order->addStatusHistoryComment($sDescription)
                        ->setIsCustomerNotified($notified);

                } else {
                    $order->addStatusHistoryComment(Mage::helper('p24acquire')->__('Error during creation of invoice.'))
                        ->setIsCustomerNotified($notified = true);
                }
                break;

            case self::STATUS_FAILURE:
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED, $newOrderStatus,
                    Mage::helper('p24acquire')->__('Privat24 error.'),
                    $notified = true
                );
                break;

            case self::STATUS_WAIT_SECURE:
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus,
                    Mage::helper('p24acquire')->__('Waiting for verification from the Privat24 side.'),
                    $notified = true
                );
                break;

            case self::STATUS_WAIT_ACCEPT:
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus,
                    Mage::helper('p24acquire')->__('Waiting for accepting from the buyer side.'),
                    $notified = true
                );
                break;

            default:
                Mage::throwException(Mage::helper('p24acquire')->__('Unexpected status from server: %s', $state));
                break;

        }
        
        $order->save();
    }
    
    /**
     * Parse data
     * 
     * @param array $data
     * @return array
     */
    protected function parseDate($data)
    {
        $exploded_data = explode('&', $data);
        $parsed_data   = array();
        
        if(array($exploded_data)) {
            foreach ($exploded_data as $string) {
                $exploded_string = explode('=', $string);
                $parsed_data[$exploded_string[0]] = isset($exploded_string[1]) ? $exploded_string[1] : '';
            }
        }
        
        return $parsed_data;
    }
    
    /**
     * Round amount price with precision from store settings
     * 
     * @param integer|float $_amount
     * @return integer|float
     */
    protected function roundTotals($_amount)
    {
        $_precision = Mage::getStoreConfig(self::XML_PATH_ROUND_PRECISION, Mage::app()->getStore()->getId());
        
        return $_precision ? round($_amount, $_precision) : round($_amount);
    }
}
