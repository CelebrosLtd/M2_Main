<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Main\ViewModel\Debug;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Celebros\Main\Helper\Debug;

class Messages implements ArgumentInterface
{
    /**
     * @var Debug
     */
    private $helper;

    /**
     * Messages constructor
     *
     * @param Debug $helper
     * @return void
     */
    public function __construct(
        Debug $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * If debug is enabled
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->helper->isEnabled();
    }

    /**
     * Get debug messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->helper->getMessages();
    }
}
