<?php

/**
 * P24Acquire Language source model
 *
 * @category   NoName
 * @package    NoName_P24Acquire
 * @author     https://github.com/evgv
 * @version    1.0.0
 */

class NoName_P24Acquire_Model_System_Config_Source_Language
{
    const LANGUAGE_UA = 'UA';
    const LANGUAGE_RU = 'RU';
    const LANGUAGE_EN = 'EN';
    const LANGUAGE_LV = 'LV';

    /**
     * Options array
     * 
     * @var array 
     */
    protected $options = array();

    /**
     * Retrieve p24acquire payment method allowed currency codes
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $this->options = array(
                            array(
                                'value' => self::LANGUAGE_UA, 
                                'label' => Mage::helper('p24acquire')->__('Ukranian')
                            ),
                            array(
                                'value' => self::LANGUAGE_RU, 
                                'label' => Mage::helper('p24acquire')->__('Russian')
                            ),
                            array(
                                'value' => self::LANGUAGE_EN, 
                                'label' => Mage::helper('p24acquire')->__('English')
                            ),
                            array(
                                'value' => self::LANGUAGE_LV, 
                                'label' => Mage::helper('p24acquire')->__('Latvian')
                            )
                        );
        
        return $this->options;
    }
}


