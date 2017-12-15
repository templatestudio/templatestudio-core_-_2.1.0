<?php
/**
 * Copyright © 2017 Templatestudio UK. All rights reserved.
 */

namespace Templatestudio\Core\Model;

use Templatestudio\Core\Model\Vendor\Config as VendorConfig;
use Templatestudio\Core\Helper\Data as CoreHelper;
use Templatestudio\Core\App\Module\ModuleListInterface;
use Magento\Framework\FlagFactory;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Module\Dir\Reader as DirReader;
use Magento\Framework\HTTP\PhpEnvironment\ServerAddress;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class License implements LicenseInterface
{

    /**
     * URL Manager
     * 
     * @var null|UrlInterface
     */
    protected $urlManager;

    /**
     * Module list
     * 
     * @var null|ModuleListInterface
     */
    protected $moduleList;

    /**
     * cURL Factory
     * 
     * @var null|CurlFactory
     */
    protected $curlFactory;

    /**
     * Flag factory
     * 
     * @var null|FlagFactory
     */
    protected $flagFactory;

    /**
     * Magento Metadata
     * 
     * @var null|ProductMetadata
     */
    protected $productMetadata;

    /**
     * Deployment configuration
     * 
     * @var null|DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * Directory reader
     * 
     * @var null|DirReader
     */
    protected $dirReader;

    /**
     * Library for working with server ip address
     *
     * @var null|ServerAddress
     */
    protected $serverAddress;

    /**
     * Vendor configuration
     *
     * @var null|VendorConfig
     */
    protected $vendorConfig;

    /**
     * Store manager
     *
     * @var null|StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Scope configuration
     *
     * @var null|ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Vendor domain name
     *
     * @var null|string
     */
    protected $vendorDomainName;

    /**
     * License
     * 
     * @var null|string
     */
    private $license;

    /**
     * Module name
     * 
     * @var null|string
     */
    private $moduleName;

    /**
     * Flag data
     *
     * @var null|\Magento\Framework\Flag
     */
    private $flagData;

    /**
     * Constructor
     * 
     * @param UrlInterface $urlManager
     * @param ModuleListInterface $moduleList
     * @param CurlFactory $curlFactory
     * @param FlagFactory $flagFactory
     * @param ProductMetadata $productMetadata
     * @param DeploymentConfig $deploymentConfig
     * @param DirReader $dirReader
     * @param VendorConfig $vendorConfig
     * @param ServerAddress $serverAddress
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface $urlManager,
        ModuleListInterface $moduleList,
        CurlFactory $curlFactory,
        FlagFactory $flagFactory,
        ProductMetadata $productMetadata,
        DeploymentConfig $deploymentConfig,
        DirReader $dirReader,
        VendorConfig $vendorConfig,
        ServerAddress $serverAddress,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->urlManager = $urlManager;
        $this->moduleList = $moduleList;
        $this->curlFactory = $curlFactory;
        $this->flagFactory = $flagFactory;
        $this->productMetadata = $productMetadata;
        $this->deploymentConfig = $deploymentConfig;
        $this->dirReader = $dirReader;
        $this->vendorConfig = $vendorConfig;
        $this->serverAddress = $serverAddress;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function load($module)
    {
        $moduleDir = $this->getModuleDir($module);
        if (! $moduleDir) {
            return true;
        }

        $this->moduleName = $module;
        $this->license = null;

        $license = $this->scopeConfig->getValue(
            CoreHelper::getLicenseConfigPath($module)
        );

        if (null === $license or '' === ($license = trim($license))) {
            $licenseFile = $moduleDir . DIRECTORY_SEPARATOR . 'license';
            if (file_exists($licenseFile) and is_file($licenseFile) and is_readable($licenseFile)) {
                $license = @file_get_contents($licenseFile);
                if ($license) {
                    $this->license = $license;
                }
            }
        } else {
            $this->license = $license;
        }

        return $this->license;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByClassName($className)
    {
        $module = $this->getModuleName($className);

        if (false === $module) {
            return false;
        }

        return $this->load($module);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus($moduleName)
    {
        if (false !== strpos($this->getDomain(), $this->getVendorDomainName())) {
            return true;
        }

        $this->load($moduleName);

        if ($this->isNeedUpdate()) {
            $this->request();
            $data = $this->getFlagData();

            if (isset($data['status']) and $data['status'] === self::STATUS_ACTIVE) {
                return true;
            }

            if (isset($data['message'])) {
                return $data['message'];
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * {@inheritdoc}
     */
    public function getVendor()
    {
        return $this->moduleList->getVendor();
    }

    /**
     * Retrive module directory
     * 
     * @param string $module
     * @return false|string
     */
    private function getModuleDir($module)
    {
        return $this->dirReader->getModuleDir('', $module);
    }

    /**
     * Retrieve module name by class
     * 
     * @param null|string $className
     * @return false|string
     */
    private function getModuleName($className = null)
    {
        if (null === $className) {
            $className = get_class($this);
        }

        $class = explode('\\', $className);
        if (2 <= count($class)) {
            return implode('_', array_slice($class, 0, 2));
        }

        return false;
    }

    /**
     * Send request with all required data
     *
     * @return $this
     */
    private function request()
    {
        $params = [
            'd' => $this->getDomain(),
            'bu' => $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB
            ),
            'sa' => $this->getServerAddress(),
            'mv' => $this->getMagentoVersion(),
            'me' => $this->getMagentoEdition(),
            'mn' => $this->moduleName,
            'l' => $this->getLicense(),
            'uid' => $this->getUID()
        ];

        $result = $this->sendRequest(
            $this->vendorConfig->getLicenseUrl(),
            $params
        );

        $result['time'] = time();
        $this->saveFlagData($result);

        return $this;
    }

    /**
     * Check whether update is required
     * 
     * @return bool
     */
    public function isNeedUpdate()
    {
        $data = $this->getFlagData();

        if (! $data or ! is_array($data)) {
            return true;
        }

        if (array_key_exists('status', $data) and $data['status'] === self::STATUS_ACTIVE) {
            $curTime = time();
            if (! isset($data['time']) or ! is_numeric($data['time'])
                or $curTime < $data['time'] or $curTime - $data['time'] > 86400) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * Save request result to flag
     *
     * @param array $data
     * @return $this
     */
    private function saveFlagData(array $data)
    {
        if ($this->moduleName) {
            $this->getFlag()
                ->setFlagData(base64_encode(serialize($data)))
                ->save();
        }

        return $this;
    }

    /**
     * Return last request result
     *
     * @return array
     */
    private function getFlagData()
    {
        $flag = $this->getFlag();

        if ($flag->getFlagData()) {
            $data = @unserialize(@base64_decode($flag->getFlagData()));

            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    /**
     * Remove flag data
     *
     * @return $this
     */
    public function clear()
    {
        $this->getFlag()->delete();
        return $this;
    }

    /**
     * Remove flag data
     *
     * @return \Magento\Framework\Flag
     */
    private function getFlag()
    {
        if (null === $this->flagData) {
            $this->flagData = $this->flagFactory->create(
                [
                    'data' => [
                        'flag_code' => 'license_' . $this->moduleName . '_' . $this->license
                    ]
                ]
            )->loadSelf();
        }
        return $this->flagData;
    }

    /**
     * Retrieve vendor domain name
     *
     * @return string
     */
    public function getVendorDomainName()
    {
        if (null === $this->vendorDomainName) {
            $domainName = strtolower(parse_url(
                $this->vendorConfig->getVendorUrl(),
                PHP_URL_HOST
            ));
            $this->vendorDomainName = preg_replace(
                '#^www\.(.+\.)#i',
                '$1',
                $domainName
            );
        }
        return $this->vendorDomainName;
    }

    /**
     * Send http request
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    private function sendRequest($endpoint, $params)
    {
        $curl = $this->curlFactory->create();
        $config = ['timeout' => 10];

        $curl->setConfig($config);
        $curl->write(
            \Zend_Http_Client::POST,
            $endpoint,
            \Zend_Http_Client::HTTP_1,
            [],
            http_build_query($params, '', '&')
        );
        $response = $curl->read();
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);

        $response = @unserialize($response);

        if (is_array($response)) {
            return $response;
        }

        return [];
    }

    /**
     * Backend domain
     *
     * @return string
     */
    private function getDomain()
    {
        return $this->urlManager->getCurrentUrl();
    }

    /**
     * Server IP
     *
     * @return string
     */
    private function getServerAddress()
    {
        return $this->serverAddress->getServerAddress();
    }

    /**
     * Magento edition
     *
     * @return string
     */
    private function getMagentoEdition()
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * Magento version
     *
     * @return string
     */
    private function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Unique installation key
     *
     * @return string
     */
    private function getUID()
    {
        $db = $this->deploymentConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
            . '/' . ConfigOptionsListConstants::KEY_NAME
        );

        $host = $this->deploymentConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
            . '/' . ConfigOptionsListConstants::KEY_HOST
        );

        return md5($db . $host);
    }
}
