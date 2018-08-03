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
namespace Celebros\Main\Observer;
use Psr\Log\LoggerInterface as Logger;

use Magento\Framework\Event\ObserverInterface;
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Response as HttpResponse;
use Zend\Uri\Http as HttpUri;

class ReleaseNotification implements ObserverInterface
{
    const MODULE_NAME = 'Celebros_Celexport';
    const GITHUB_API_RELEASE_LINK = 'https://api.github.com/repos/CelebrosLtd/M2_Celexport/releases/latest';
    const CACHE_POSTFIX = '_Last_Release';
    
    protected $githubApi = [
        'Celebros_ConversionPro' => 'https://api.github.com/repos/devbelvg/M2_ConversionPro_Embedded/releases/latest',
        'Celebros_Celexport' => 'https://api.github.com/repos/CelebrosLtd/M2_Celexport/releases/latest',
        'Celebros_AutoComplete' => 'https://api.github.com/repos/CelebrosLtd/M2_AutoComplete/releases/latest',
        'Celebros_Main' => 'https://api.github.com/repos/devbelvg/M2_Main/releases/latest'
    ];
    
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Celebros\Main\Helper\Data $helper,
        \Magento\Framework\Module\ResourceInterface $moduleDb,
        \Magento\AdminNotification\Model\Inbox $notification,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->helper = $helper;
        $this->_notification = $notification;
        $this->_moduleDb = $moduleDb;
        $this->cache = $cache;
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
    
    public function checkNewRelease($module)
    {
        $newNotification = true;
        $version = $this->getLatestRelease($this->githubApi[$module['name']]);
        $text = 'Celebros is regularly releasing new versions of our Magento extensions to add more features, fix bugs, or to be compatible with the new Magento version. Therefore, you also have to update Magento extensions on your site to the most recent version.';
        if ($version) {
            $notifications = $this->_notification
                ->getCollection()
                ->addFieldToFilter('url', $this->lRelease->html_url)
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
                $this->_notification->addCritical($this->lRelease->body . ' is available', $text, $this->lRelease->html_url);
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
        $version = null;
        try {
            $curlClient = new Curl();
            if (!$location) {
                $location = self::GITHUB_API_RELEASE_LINK;
            }
            
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

            $this->lRelease = json_decode($data->getContent());          

            $version = $this->lRelease->tag_name;
        } catch (\Exception $e) {
            return false;
        }

        return $version;
    }
}