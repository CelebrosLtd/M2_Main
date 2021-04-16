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
        $expand = false;
        $html = '';
        $id = $element->getHtmlId();
        foreach ($this->getCelebrosModules() as $module) {
            $version = $this->cache->load($module['name'] . '_Last_Release');
            $releases = json_decode($this->cache->load($module['name'] . '_releases'), true);
            $versions = '<div class="release-item installed"><div>' . $module['setup_version'] . '</div><div class="release-status current"><span>' . __('Installed') .'</span></div></div></div>';
            if (is_array($releases) && !empty($releases)) {
                $releases = array_reverse($releases);
                if (count($releases) > 1) {
                    $expand = true;
                    $versions .= '<div class="release-item expand" onclick="expandReleases(this)"><div> </div><span>' . __('more versions') .'</span></div></div>';
                }

                end($releases);
                $last = key($releases);
                foreach ((array)$releases as $key => $r) {
                    $class = ($last == $key) ? ' last' : ' hidden';
                    $rel = ($r['status'] == 'Critical') ? 'critical' : 'stable';
                    $releaseText = ($last == $key) ? '<div class="release-comment">'. __('Most recent %1 release', __($rel)) . '</div>' : '';
                    $versions .= '<div class="release-item' . $class . '"><div>' . $r['version'] . '</div><div class="release-status ' . strtolower($r['status']) . '"><span>' . __($r['status']) . '</span></div><a target="_blank" href="' . $r['url'] .'"><div class="github-link"><span>github</span></div></a>' . $releaseText .'</div>';
                }
            }

            $html .= '<tr id="row_' . $id . '">';
            $html .= '<td class="label">' . str_replace("Celebros_", " ", $module['name']) . '</td><td class="value"><span style="float:left;">' . $versions . '</span></td><td class="scope-label"></td>';
            $html .= '</tr>';
        }

        if ($expand) {
            $html .= '<script>function expandReleases(obj) { jQuery(obj).parent().parent().find(".hidden").removeClass("hidden"); jQuery(obj).addClass("hidden"); }</script>';
        }

        return $html;
    }

    public function getCelebrosModules()
    {
        return $this->helper->getCelebrosModules();
    }
}
