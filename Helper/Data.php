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
use Magento\Catalog\Model\Category;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Message\MessageInterface as MessageInterface;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Response as HttpResponse;
use Laminas\Uri\Http as HttpUri;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $releaseStatuses = [
        'Critical',
        'Major',
        'Normal',
        'Minor'
    ];

    protected $githubApi = [
        'celebros/module-conversionpro' =>
            'https://api.github.com/repos/CelebrosLtd/M2_ConversionPro/releases',
        'celebros/module-celexport' =>
            'https://api.github.com/repos/CelebrosLtd/M2_Celexport/releases',
        'celebros/module-autocomplete' =>
            'https://api.github.com/repos/CelebrosLtd/M2_AutoComplete/releases',
        'celebros/module-main' =>
            'https://api.github.com/repos/devbelvg/M2_Main/releases',
        'celebros/module-conversionpro-embedded' =>
            'https://api.github.com/repos/devbelvg/M2_ConversionPro_Embedded/releases',
        'celebros/module-crosssell' =>
            'https://api.github.com/repos/devbelvg/M2_Celebros_Crosssell/releases',
        'celebros/module-sorting' =>
            'https://api.github.com/repos/CelebrosLtd/M2_Celebros_Sorting/releases',
        'celebros/module-conflictfixer' =>
            'https://api.github.com/repos/CelebrosLtd/Celebros_ConflictFixer/releases'
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

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    private $moduleReader;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @var array
     */
    private $debugModules = [];

    /**
     * @param Context $context
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param array $debugModules
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Serialize\Serializer\Json $json,
        array $debugModules = []
    ) {
        $this->cache = $cache;
        $this->moduleReader = $moduleReader;
        $this->json = $json;
        $this->debugModules = $debugModules;
        parent::__construct($context);
    }

    public function getCelebrosModules()
    {
        if (empty($this->celebrosModules)) {
            $result = [];
            foreach ($this->moduleReader->getComposerJsonFiles() as $json) {
                if (strpos((string) $json, 'Celebros') !== false) {
                    $moduleData = $this->json->unserialize($json);
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
            $data = $this->json->unserialize($this->getData($location));
            $newReleases = [];
            foreach ((array)$data as $release) {
                if (is_object($release)
                    && version_compare($release->tag_name, $cVersion, '>')
                ) {
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
                $cacheData = (string)$this->json->serialize($newReleases);
            } else {
                $cacheData =  false;
            }

            $this->cache->save($cacheData, $packageName . '_releases');
        }
    }

    protected function _extractRelStatus($string)
    {
        foreach ($this->releaseStatuses as $status) {
            if (strpos((string) $string, (string) $status) !== false) {
                return $status;
            }
        }

        return $this->releaseStatuses[2];
    }

    public function getData($location)
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

    public function collectCSPUrls($store = null): array
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

    public function isDebugEnabled($store = null): bool
    {
        $status = false;
        foreach ($this->debugModules as $debugConfigPath) {
            $status = $status || $this->scopeConfig->getValue(
                $debugConfigPath,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        }

        return $status;
    }
}
