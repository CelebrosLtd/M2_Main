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
namespace Celebros\Main\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Setup\ModuleContextInterface;
class Extensions extends \Magento\Config\Block\System\Config\Form\Field
{
    const MODULE_NAME = 'Celebros_Main';
    protected $helper;
    
    public function __construct(
        \Celebros\Main\Helper\Data $helper,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->helper = $helper;
        $this->cache = $cache;
    }
    
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        $id = $element->getHtmlId();
        foreach ($this->getCelebrosModules() as $module) {
            
            $notification = '';
            if ($version = $this->cache->load($module['name'] . '_Last_Release')) {
                $notification = '<span style="color:green;"><img style="margin-left:5px;margin-right:5px;margin-bottom:-3px;" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACHElEQVR42o2Ty08TURSHv4G2lPGBpQWsGlkoQnwsTC3WND6xLNSNG5cEkUaJmNg/wBjjgkRDFB9RtAprF8ZoDNE2EjWQRjCIjwVqF01EIo2Q1lhqp6HeTsXOFIyc5E5+58zJd8+591yJQuvb7sBqOi7UPrGq/0QjYj0jpnTjCb3Wpkt/1ZXNMi7LVaGO6uJ6y4jVw2jsFN63iTyga5PMjvKnQrlZnA0ISGMWkgO82nlHfFsKs66t9YEyS/uXy1A8r6i71L88JvHQ6WCleaiw7FbbIQ4anKp+PDmIP9kHpiJ9OzHFKYndbwnHq/3jWrKRG3Yfu560qv6LRj9twx2ESj6BXKxNvZ0FfBZi3VzEbrQSrLnE4aCPj/GIGtuwvJoHezppCJ5gouwHlBnn0sNZgCKEIeuZJAP9tV08jwxxfewe44lJNWu1XMnJ2iPsrnSwN+AlZRHd2kyiaSmtA6wy2ji3ookqQznfZr7jDZ3P1ek6Q1WpVY2dHb3J15koLBOt2M3peS2QzuBW6mi2H9ABesOPGIi+0d9DSVF4wUN0L91Cs2H//wHgl7i/zcGaUt01LhKQIa7ULzhIW+UaAus7iSanVb/CbMETaGNkekwL6KFppCUH6KiTaajIj3JGjPyUONto6l+jPMj7uIcL4UR++nIQ/WOKCcjEL33Z0Mu7eDsXw5rHpLXcmeSf8880jCcjzNLPVKqb0x+Gtem/AS1KwNa5iRb2AAAAAElFTkSuQmCC">' . $version . ' is available</span>';
            }
            
            $html .= '<tr id="row_' . $id . '">';
            $html .= '<td class="label">' . str_replace("Celebros_", " ", $module['name']) . '</td><td class="value">' . $module['setup_version'] . $notification . '</td><td class="scope-label"></td>';
            $html .= '</tr>';
        }
        return $html;
    }

    public function getCelebrosModules()
    {
        return $this->helper->getCelebrosModules();       
    }
}