<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\Main\Observer;

use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Event\ObserverInterface;

class ReleaseNotification implements ObserverInterface
{
    public const MODULE_NAME = 'Celebros_Main';
    public const CACHE_POSTFIX = '_Last_Release';

    /**
     * @var string[]
     */
    protected $_releaseDescrRSymbols = [
        "-----", "["
    ];

    /**
     * @var \stdClass
     */
    private $latestRelease;
    /**
     * @var \Celebros\Main\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    private $moduleDb;

    /**
     * @var \Magento\AdminNotification\Model\Inbox
     */
    private $notification;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @var string[]
     */
    private $githubApi;

    /**
     * @param \Celebros\Main\Helper\Data $helper
     * @param \Magento\Framework\Module\ResourceInterface $moduleDb
     * @param \Magento\AdminNotification\Model\Inbox $notification
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */
    public function __construct(
        \Celebros\Main\Helper\Data $helper,
        \Magento\Framework\Module\ResourceInterface $moduleDb,
        \Magento\AdminNotification\Model\Inbox $notification,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->helper = $helper;
        $this->notification = $notification;
        $this->moduleDb = $moduleDb;
        $this->cache = $cache;
        $this->json = $json;
        $this->githubApi = $this->helper->getGithubApi();
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $modules = $this->helper->getCelebrosModules();
        foreach ($modules as $module) {
            if (isset($this->githubApi[$module['name']])) {
                $this->checkNewRelease($module);
            }
        }
    }

    public function extractReleaseShortDescription(string $releaseBody): string
    {
        foreach ($this->_releaseDescrRSymbols as $symb) {
            if (strpos($releaseBody, (string) $symb) !== false) {
                $releaseBody = substr($releaseBody, 0, strpos($releaseBody, (string) $symb));
            }
        }

        return $releaseBody;
    }

    public function checkNewRelease($module)
    {
        $newNotification = true;
        $this->helper->collectNewReleases($module['name']);
        $release = $this->getLatestRelease($this->githubApi[$module['name']]);
        $version = $release instanceof \stdClass ? $release->tag_name : false;
        if ($version) {
            $notifications = $this->notification
                ->getCollection()
                ->addFieldToFilter('url', $release->html_url)
                ->addFieldToFilter('is_remove', 0);

            foreach ($notifications as $notification) {
                if ($notification->getNotificationId()) {
                    //if ($notification->getIsRemove() != 1) {
                        $newNotification = false;
                    //}
                }
            }
        }

        if ($version && !version_compare($module['setup_version'], $version, '=')) {
            if ($newNotification) {
                $this->notification->addCritical(
                    $this->extractReleaseShortDescription($release->body) . ' is available',
                    __('celebros_main::NOTIFICATION_TEXT'),
                    $release->html_url
                );
            }

            $this->cache->save((string)$version, $module['name'] . self::CACHE_POSTFIX);
        } else {
            $this->cache->remove($module['name'] . self::CACHE_POSTFIX);
        }
    }

    public function getModuleVersion()
    {
        return $this->moduleDb->getDbVersion(self::MODULE_NAME);
    }

    protected function getLatestRelease($location = null)
    {
        $location .= '/latest';
        try {
            $this->latestRelease = $this->json->unserialize($this->helper->getData($location));
        } catch (\Exception $e) {
            return false;
        }

        return $this->latestRelease;
    }
}
