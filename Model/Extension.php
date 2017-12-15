<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Model;

use Magento\Framework\Unserialize\Unserialize;

class Extension extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Default check frequency
     */
    const DEFAULT_CHECK_FREQUENCY = 86400;

    /**#@+
     * Cache ID
     */
    const CACHE_IDENTIFIER = 'templatestudio_extensions';
    const CACHE_LASTCHECK_IDENTIFIER = 'templatestudio_extensions_lastcheck';
    /**#@-*/

    /**
     * CURL Factory
     * 
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $curlFactory;

    /**
     * Deployment configuration
     *
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * Magento Metadata
     * 
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * URL Builder
     * 
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Library for working with server ip address
     * 
     * @var \Magento\Framework\HTTP\PhpEnvironment\ServerAddress
     */
    protected $serverAddress;

    /**
     * Module statuses manager
     * 
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * Templatestudio module list
     * 
     * @var \Templatestudio\Core\App\Module\ModuleList
     */
    protected $moduleList;

    /**
     * Magento Simple XML Element Factory
     * 
     * @var \Magento\Framework\Simplexml\ElementFactory
     */
    protected $xmlElementFactory;

    /**
     * Object Factory
     * 
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Templatestudio\Core\Model\Vendor\Config $vendorConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Templatestudio\Core\App\Module\ModuleList $moduleList
     * @param \Magento\Framework\Simplexml\ElementFactory $xmlElementFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Templatestudio\Core\Model\Vendor\Config $vendorConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress,
        \Magento\Framework\Module\Manager $moduleManager,
        \Templatestudio\Core\App\Module\ModuleList $moduleList,
        \Magento\Framework\Simplexml\ElementFactory $xmlElementFactory,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->curlFactory = $curlFactory;
        $this->deploymentConfig = $deploymentConfig;
        $this->productMetadata = $productMetadata;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->serverAddress = $serverAddress;
        $this->moduleManager = $moduleManager;
        $this->moduleList = $moduleList;
        $this->vendorConfig = $vendorConfig;
        $this->xmlElementFactory = $xmlElementFactory;
        $this->objectFactory = $objectFactory;
    }

    /**
     * Retrieve extensions url
     *
     * @return string|null
     */
    public function getFeedUrl()
    {
        return $this->vendorConfig->getUrl('extension');
    }

    /**
     * Retrieve frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        if (null !== $this->_getData('frequency')) {
            return $this->_getData('frequency');
        }

        return self::DEFAULT_CHECK_FREQUENCY;
    }

    /**
     * Check extension feed for modification
     *
     * @return $this
     */
    public function checkUpdate()
    {
        if (! $this->getModulesData() or (time() - $this->getLastUpdate()) > $this->getFrequency()) {
            $data = $this->getFeedData();

            if (! empty($data) and $data instanceof \Magento\Framework\Simplexml\Element) {
                $extensions = [];
                foreach ($data->children() as $extension) {
                    $extensions[$extension->name->__toString()] = $extension->asCanonicalArray();
                }

                $this->_cacheManager->save(serialize($extensions), self::CACHE_IDENTIFIER);
                $this->setLastUpdate();
            }
        }

        return $this;
    }

    /**
     * Retrieve modules data
     *
     * @param string $module Optional
     * @return string[]
     */
    public function getModulesData($module = null)
    {
        $extensions = $this->_cacheManager->load(self::CACHE_IDENTIFIER);

        if (! empty($extensions)) {
            $extensions = @unserialize($extensions);

            if (is_array($extensions)) {
                if (null !== $module and isset($extensions[$module])) {
                    return $extensions[$module];
                } elseif (null !== $module and ! isset($extensions[$module])) {
                    return [];
                }
                return $extensions;
            }
        }

        return [];
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load(self::CACHE_LASTCHECK_IDENTIFIER);
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), self::CACHE_LASTCHECK_IDENTIFIER);
        return $this;
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return SimpleXMLElement|boolean
     */
    public function getFeedData()
    {
        if (! $this->getFeedUrl()) {
            return false;
        }

        $curl = $this->curlFactory->create();
        $curl->setConfig([
            'timeout' => 2,
            'useragent' => $this->productMetadata->getName()
                . '/' . $this->productMetadata->getVersion()
                . ' (' . $this->productMetadata->getEdition() . ')',
            'referer'   => $this->urlBuilder->getUrl('*/*/*')
        ]);

        $curl->write(
            \Zend_Http_Client::POST,
            $this->getFeedUrl(),
            \Zend_Http_Client::HTTP_1,
            ['Content-Type: multipart/form-data'],
            ['xmldata' => $this->prepareXml()]
        );
        $data = $curl->read();

        if (false === $data) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml = $this->xmlElementFactory->create(array('data' => $data));
        } catch (\Exception $e) {
            return false;
        }

        return $xml;
    }

    /**
     * Prepare XML
     *
     * @param bool $asXML Optional
     * @return string|\Magento\Framework\Simplexml\Element
     */
    protected function prepareXml($asXML = true)
    {
        $xml = $this->xmlElementFactory->create(array('data' => '<?xml version="1.0" encoding="utf-8"?><customer/>'));

        $xml->addChild('base_url',
            $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB
            )
        );
        $xml->addChild('server_addr', $this->serverAddress->getServerAddress());

        $extensions = $xml->addChild('modules');

        foreach ($this->moduleList->getAll() as $module) {
            $extension = $extensions->addChild($module['name']);
            $extension->addChild('name', $module['name']);
            $extension->addChild('version', $module['setup_version']);
            $extension->addChild('enabled', (int) $this->moduleManager->isOutputEnabled($module['name']));
            $extension->addChild('edition', $this->productMetadata->getEdition());

            $config = $this->objectFactory->create();
            $default = $extension->asCanonicalArray();
            $this->_eventManager->dispatch('tscore_extension_update_xml', [
                'module' => $module['name'],
                'config' => $config,
                'default' => $default
            ]);
            $this->_eventManager->dispatch('tscore_extension_update_xml_' . strtolower($module['name']), [
                'config' => $config,
                'default' => $default
            ]);

            if (! $config->isEmpty()) {
                $additionalData = $this->xmlElementFactory->create(array('data' => $config->toXml()));;
                $extension->extend($additionalData);
            }
        }

        if (true === $asXML) {
            return $xml->asXML();
        }

        return $xml;
    }
}