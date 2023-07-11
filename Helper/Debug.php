<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Main\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

class Debug extends AbstractHelper
{
    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var array
     */
    private $debugModules = [];

    /**
     * Debug constructor
     *
     * @param Context $context
     * @param array $debugModules
     */
    public function __construct(
        Context $context,
        array $debugModules = []
    ) {
        $this->debugModules = $debugModules;
        parent::__construct($context);
    }

    /**
     * If debug is enabled
     *
     * @param $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        $status = false;
        foreach ($this->debugModules as $debugConfigPath) {
            $status = $status || $this->scopeConfig->getValue(
                $debugConfigPath,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return $status && !empty($this->messages);
    }

    /**
     * Add debug message
     *
     * @param string $message
     * @return void
     */
    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Get debug messages
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
