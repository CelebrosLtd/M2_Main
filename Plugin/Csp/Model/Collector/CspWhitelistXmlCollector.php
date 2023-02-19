<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Main\Plugin\Csp\Model\Collector;

use Magento\Csp\Model\Policy\FetchPolicy;
use Celebros\Main\Helper\Data as Helper;

class CspWhitelistXmlCollector
{
    /**
     * @var \Celebros\Main\Helper\Data
     */
    protected $helper;

    /**
     * @param \Celebros\Main\Helper\Data $helper
     * @return void
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @var \Magento\Csp\Model\Collector\CspWhitelistXmlCollector $collector
     * @var array $policies
     * @return array
     */
    public function afterCollect(\Magento\Csp\Model\Collector\CspWhitelistXmlCollector $collector, $policies)
    {
        if (!empty($this->helper->collectCSPUrls())) {
            foreach ($this->helper->collectCSPUrls() as $type => $urls) {
                $policies[] = $this->createNewPolicy($urls, $type);
            }
        }

        return $policies;
    }

    /**
     * Create new policy object
     *
     * @var array $urls
     * @var string $type
     * @return FetchPolicy
     */
    protected function createNewPolicy(
        array $urls,
        string $type
    ): FetchPolicy {
        return new FetchPolicy(
            $type,
            false,
            (array)$urls,
            [],
            false,
            false,
            false,
            [],
            [],
            false,
            false
        );
    }
}
