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
    protected $githubApi = [
        'celebros/module-conversionpro' => 'https://api.github.com/repos/CelebrosLtd/M2_ConversionPro/releases/latest',
        'celebros/module-celexport' => 'https://api.github.com/repos/CelebrosLtd/M2_Celexport/releases/latest',
        'celebros/module-autocomplete' => 'https://api.github.com/repos/CelebrosLtd/M2_AutoComplete/releases/latest',
        'celebros/module-main' => 'https://api.github.com/repos/devbelvg/M2_Main/releases/latest',
        'celebros/module-conversionpro-embedded' => 'https://api.github.com/repos/devbelvg/M2_ConversionPro_Embedded/releases/latest'
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
        $result = [];
        foreach ($this->moduleReader->getComposerJsonFiles() as $json) {
            if (strpos($json, 'Celebros') !== false ) {
                $moduleData = $this->jsonHelper->jsonDecode($json);
                if (isset($moduleData['name'])) {
                    $item = [
                        'name' => $moduleData['name'],
                        'setup_version' => $moduleData['version']
                    ];
                    $result[] = $item;
                }
            }
        }
        
        return $result;
    }
    
    public function getGithubApi()
    {
        return $this->githubApi;
    }
    
    public function getLatestRelease($location = null)
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
