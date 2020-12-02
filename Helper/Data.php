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
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Response as HttpResponse;
use Zend\Uri\Http as HttpUri;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $releaseStatuses = [
        'Critical',
        'Major',
        'Normal',
        'Minor'
    ];
    
    protected $githubApi = [
        'celebros/module-conversionpro' => 'https://api.github.com/repos/CelebrosLtd/M2_ConversionPro/releases',
        'celebros/module-celexport' => 'https://api.github.com/repos/CelebrosLtd/M2_Celexport/releases',
        'celebros/module-autocomplete' => 'https://api.github.com/repos/CelebrosLtd/M2_AutoComplete/releases',
        'celebros/module-main' => 'https://api.github.com/repos/devbelvg/M2_Main/releases',
        'celebros/module-conversionpro-embedded' => 'https://api.github.com/repos/devbelvg/M2_ConversionPro_Embedded/releases',
        'celebros/module-crosssell' => 'https://api.github.com/repos/devbelvg/M2_Celebros_Crosssell/releases',
        'celebros/module-sorting' => 'https://api.github.com/repos/CelebrosLtd/M2_Celebros_Sorting/releases',
        'celebros/module-conflictfixer' => 'https://api.github.com/repos/CelebrosLtd/Celebros_ConflictFixer/releases'
    ];
    
    protected $celebrosModules = [];
    
    protected $cspXmlPaths = [
        \Celebros\AutoComplete\Helper\Data::XML_PATH_SCRIPT_SERVER_ADDRESS => ['script-src', 'style-src'],
        \Celebros\AutoComplete\Helper\Data::XML_PATH_FRONTEND_SERVER_ADDRESS => 'font-src',
        \Celebros\ConversionPro\Helper\Data::XML_PATH_ANALYTICS_HOST => 'script-src'
    ];
    
    protected $cspUrls = [
        'ajax.googleapis.com' => 'script-src',
        '*.celebros.com' => ['script-src', 'connect-src'],
        '*.celebros.com:446' => 'connect-src',
        '*.celebros-analytics.com' => 'connect-src',
        'celebrosnlp.com' => 'img-src'
    ];
    
    public function __construct(
        Context $context,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->cache = $cache;
        $this->moduleReader = $moduleReader;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }
    
    public function getCelebrosModules()
    {
        if (empty($this->celebrosModules)) {
            $result = [];
            foreach ($this->moduleReader->getComposerJsonFiles() as $json) {
                if (strpos($json, 'Celebros') !== false) {
                    $moduleData = $this->jsonHelper->jsonDecode($json);
                    if (isset($moduleData['name'])) {
                        $item = [
                            'name' => $moduleData['name'],
                            'setup_version' => $moduleData['version']
                        ];
                        
                        $result[$moduleData['name']] = $item;
                    }
                }
            }
            
            $this->celebrosModules = $result;
        }
        
        return $this->celebrosModules;
    }
    
    public function getCurrentVersion($packageName)
    {
        $modules = $this->getCelebrosModules();
        return isset($modules[$packageName]) ? $modules[$packageName]['setup_version'] : null;
    }
    
    public function getGithubApi()
    {
        return $this->githubApi;
    }
    
    public function collectNewReleases($packageName)
    {
        $cVersion = $this->getCurrentVersion($packageName);
        $location = isset($this->githubApi[$packageName]) ? (string)$this->githubApi[$packageName] : null;
        if ($location) {
            $data = json_decode($this->getData($location));
            $newReleases = [];
            foreach ((array)$data as $release) {
                if (is_object($release) 
                && version_compare($release->tag_name, $cVersion, '>')) {
                    $newReleases[$release->tag_name] = [
                        'url' => $release->html_url,
                        'version' => $release->tag_name,
                        'zip_url' => $release->zipball_url,
                        'body' => $release->body,
                        'status' => $this->_extractRelStatus($release->body)
                    ];
                }
            }
            
            if (!empty($newReleases)) {
                $cacheData = (string)json_encode($newReleases);
            } else {
                $cacheData =  false;
            }
            
            $this->cache->save($cacheData, $packageName . '_releases');
        }
    }
    
    protected function _extractRelStatus($string)
    {
        foreach ($this->releaseStatuses as $status) {
            if (strpos($string, $status) !== false) {
                return $status;
            }
        }
        
        return $this->releaseStatuses[2];
    }
    
    public function getLatestRelease($location = null)
    {
        $version = null;
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
            
            $this->lRelease = json_decode($data->getContent());          
            
            $version = $this->lRelease->tag_name;
        } catch (\Exception $e) {
            return false;
        }
        
        return $version;
    }
    
    protected function getData($location)
    {
        try {
            $curlClient = new Curl();
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
            
            return $data->getContent();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function collectCSPUrls($store = null) : array
    {
        $urls = [];
        foreach ($this->cspXmlPaths as $xmlPath => $type) {
            if ($url = $this->scopeConfig->getValue(
                $xmlPath,
                ScopeInterface::SCOPE_STORE,
                $store
            )) {
                $this->fillSCPUrls($urls, $type, $url);
            }
        }
        
        foreach ($this->cspUrls as $url => $type) {
            $this->fillSCPUrls($urls, $type, $url);
        }
//print_r($urls);die;       
        return $urls;
    }
    
    protected function fillSCPUrls(&$urls, $type, $url)
    {
        if (is_array($type)) {
            foreach ($type as $t) {
                $urls[$t][] = $url;
            }        
        } else {
            $urls[$type][] = $url;
        }
    }
}
