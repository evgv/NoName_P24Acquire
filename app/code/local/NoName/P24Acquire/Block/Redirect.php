<?php

/**
 * P24Acquire paymentinformation
 *
 * @category   NoName
 * @package    NoName_P24Acquire
 * @author     https://github.com/evgv
 * @version    1.0.0
 */

class NoName_P24Acquire_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * Set template with message
     */
    protected function _construct()
    {
        $this->setTemplate('p24acquire/redirect.phtml');
        parent::_construct();
    }


    /**
     * Return redirect form
     *
     * @return Varien_Data_Form
     */
    public function getForm()
    {
        $paymentMethod = Mage::getModel('p24acquire/paymentMethod');

        $form = new Varien_Data_Form();
        $form->setAction($paymentMethod->getConfigPaymentAction())
             ->setId('p24acquire_redirect')
             ->setName('p24acquire_redirect')
             ->setData('accept-charset', 'utf-8')
             ->setUseContainer(true)
             ->setMethod('POST');

        foreach ($paymentMethod->getRedirectFormFields() as $field => $value) {
            $form->addField($field,'hidden',array('name'=>$field,'value'=>$value));
        }

        return $form;
    }
}