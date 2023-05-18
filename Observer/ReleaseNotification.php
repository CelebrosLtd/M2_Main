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
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Response as HttpResponse;
use Zend\Uri\Http as HttpUri;

class ReleaseNotification implements ObserverInterface
{
    /**
     * @var \stdClass
     */
    private $latestRelease;

    public const MODULE_NAME = 'Celebros_Main';
    public const CACHE_POSTFIX = '_Last_Release';

    public $helper;
    public $cache;
    public $json;

    protected $_notification;
    protected $_moduleDb;
    protected $githubApi;
    protected $_releaseDescrRSymbols = [
        "-----", "["
    ];

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Celebros\Main\Helper\Data $helper,
        \Magento\Framework\Module\ResourceInterface $moduleDb,
        \Magento\AdminNotification\Model\Inbox $notification,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Json\Helper\Data $json
    ) {
        $this->helper = $helper;
        $this->_notification = $notification;
        $this->_moduleDb = $moduleDb;
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
            if (strpos($releaseBody, $symb) !== false) {
                $releaseBody = substr($releaseBody, 0, strpos($releaseBody, $symb));
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
            $notifications = $this->_notification
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
                $this->_notification->addCritical(
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
        return $this->_moduleDb->getDbVersion(self::MODULE_NAME);
    }

    protected function getLatestRelease($location = null)
    {
        try {
            $curlClient = new Curl();

            $location .= '/latest';

            $uri = new HttpUri($location);
            $curlClient->setOptions([
                'timeout'   => 8
            ]);

            $headers = ['User-Agent' => 'CelebrosLtd'];
            $curlClient->connect(
                $uri->getHost(),
                $uri->getPort()
            );

            $curlClient->write('GET', $uri, 1.0, $headers);
            $data = HttpResponse::fromString($curlClient->read());
            $curlClient->close();

            $this->latestRelease = json_decode($data->getContent());
        } catch (\Exception $e) {
            return false;
        }

        return $this->latestRelease;
    }
}
