<?php

/**
 * P24Acquire Currency source model
 *
 * @category   NoName
 * @package    NoName_P24Acquire
 * @author     https://github.com/evgv
 * @version    1.0.0
 */

class NoName_P24Acquire_Model_System_Config_Source_Currency
{
    const CURRENCY_UAH = 'UAH';
    const CURRENCY_RUB = 'RUB';
    const CURRENCY_EUR = 'EUR';
    const CURRENCY_USD = 'USD';

    /**
     * Options array
     * 
     * @var array 
     */
    protected $options  = array();
    
    /**
     * Currencies array
     * 
     * @var array 
     */
    protected $currency = array();

    /**
     * Retrieve p24acquire payment method allowed currency codes
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = array(
                            array(
                                'value' => self::CURRENCY_UAH, 
                                'label' => Mage::helper('p24acquire')->__('Ukrane Hrivna')
                            ),
                            array(
                                'value' => self::CURRENCY_RUB, 
                                'label' => Mage::helper('p24acquire')->__('Russina Rub')
                            ),
                            array(
                                'value' => self::CURRENCY_EUR, 
                                'label' => Mage::helper('p24acquire')->__('Euro')
                            ),
                            array(
                                'value' => self::CURRENCY_USD, 
                                'label' => Mage::helper('p24acquire')->__('Dollar USA')
                            )
                        );

        return $this->options;
    }
    
    /**
     * Retrieve p24acquire payment method allowed currencies as array
     * 
     * @return array
     */
    public function toArray()
    {
        $this->currency = array(
            self::CURRENCY_UAH,
            self::CURRENCY_RUB,
            self::CURRENCY_EUR,
            self::CURRENCY_USD
        );
                
        return $this->currency;
    }
}


