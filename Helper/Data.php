<?php
/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 ******************************************************************************
 * @category    Celebros
 * @package     Celebros_Main
 */
namespace Celebros\Main\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Category;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Message\MessageInterface as MessageInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        Context $context,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Module\ModuleListInterface $moduleList
    ) {
        $this->cache = $cache;
        $this->moduleList = $moduleList;
        parent::__construct($context);
    }
    
    public function getCelebrosModules()
    {
        $result = [];
        foreach ($this->moduleList->getAll() as $item) {
            if (isset($item['name']) && strpos($item['name'], "Celebros") !== false) {
                $result[] = $item;
            }
        }

        return $result;        
    }
}
