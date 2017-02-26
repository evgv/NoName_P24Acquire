<?php

/**
 * P24Acquire paymentinformation
 *
 * @category   NoName
 * @package    NoName_P24Acquire
 * @author     https://github.com/evgv
 * @version    1.0.0
 */

class NoName_P24Acquire_Block_PaymentInformation extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('p24acquire/payment_information.phtml');
        parent::_construct();
    }
}
