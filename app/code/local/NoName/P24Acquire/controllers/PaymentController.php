<?php

/**
 * Payment method P24Acquire controller
 *
 *
 * @category   NoName
 * @package    NoName_P24Acquire
 * @author     https://github.com/evgv
 * @version    1.0.0
 */

class NoName_P24Acquire_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Order
     * 
     * @var Mage_Sales_Model_Order 
     */
    protected $_order;

    /**
     * Redirect customer to Privat24 payment interface
     */
    public function redirectAction()
    {
        $session = $this->getSession();

        $quote_id = $session->getQuoteId();
        $last_real_order_id = $session->getLastRealOrderId();

        if (is_null($quote_id) || is_null($last_real_order_id)) {
            $this->_redirect('checkout/cart/');
        } else {
            $session->setP24QuoteId($quote_id);
            $session->setP24LastRealOrderId($last_real_order_id);

            $order = $this->getOrder();
            $order->loadByIncrementId($last_real_order_id);

            $html = $this->getLayout()->createBlock('p24acquire/redirect')->toHtml();
            $this->getResponse()->setHeader('Content-type', 'text/html; charset=utf-8')->setBody($html);

            $order->addStatusToHistory(
                $order->getStatus(),
                Mage::helper('p24acquire')->__('Customer switch over to Privat24 payment interface.')
            )->save();
            
            try {
                if(Mage::getStoreConfig('payment/p24acquire/email', Mage::app()->getStore()->getId())){
                    $order->getSendConfirmation(null);
                    $order->sendNewOrderEmail();
                }
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('p24acquire')->__('Can not send new order email.'));
            }
            
            $session->getQuote()->setIsActive(false)->save();

            $session->setQuoteId(null);
            $session->setLastRealOrderId(null);
        }
    }



    /**
     * Customer successfully got back from Privat24 payment interface
     */
    public function resultAction()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');

        $order_id = $session->getP24LastRealOrderId();
        $quote_id = $session->getP24QuoteId(true);

        $order = $this->getOrder();
        $order->loadByIncrementId($order_id);

        if ($order->isEmpty()) {
            return false;
        }

        $order->addStatusHistoryComment(
            Mage::helper('p24acquire')->__('Customer successfully got back from Privat24 payment interface.')
        );

        $order->save();
        
        $session->setQuoteId($quote_id);
        $session->getQuote()->setIsActive(false)->save();
        $session->setLastRealOrderId($order_id);

        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }

    /**
     * Validate data from P24Acquire server and update the database
     */
    public function serverAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->norouteAction();
            return;
        }
        
        $this->getP24()->processNotification($this->getRequest()->getPost());
    }


    /**
     * Session
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }


    /**
     * Order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
            $session = $this->getSession();
            $this->_order = Mage::getModel('sales/order');
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        
        return $this->_order;
    }


    /**
     * Retrieve P24Acquire payment model
     * 
     * @return NoName_P24Acquire_Model_PaymentMethod
     */
    public function getP24()
    {
        return Mage::getSingleton('p24acquire/paymentMethod');
    }
}