<?php

/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_ConversionPro
 */

namespace Celebros\Main\ViewModel\Debug;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Celebros\Main\Helper\Debug;

class Messages implements ArgumentInterface
{
    protected $messages = [];
    
    /**
     * @param Data $helper
     * @return void
     */
    public function __construct(
        Debug $helper
    ) {
        $this->helper = $helper;
    }
    
    public function isDebugEnabled(): bool
    {
        return $this->helper->isEnabled();
    }
    
    public function getMessages()
    {
        return $this->helper->getMessages();
    }
}