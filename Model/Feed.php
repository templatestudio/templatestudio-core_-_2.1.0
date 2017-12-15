<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Model;

class Feed extends \Magento\AdminNotification\Model\Feed
{

    /**
     * Cache identifier
     */
    const CACHE_IDENTIFIER = 'tscore_notification_lastcheck';

    /**
     * #@+
     * XML Config Paths
     */
    const XML_USE_HTTPS_PATH = 'tscore/notification/use_https';
    const XML_FREQUENCY_PATH = 'tscore/notification/frequency';
    const XML_LAST_UPDATE_PATH = 'tscore/notification/last_update';
    /**
     * #@-
     */

    /**
     * Vendor Config
     * 
     * @var \Templatestudio\Core\Model\Vendor\Config
     */
    protected $vendorConfig;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     * @param \Magento\AdminNotification\Model\InboxFactory $inboxFactory
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Templatestudio\Core\Model\Vendor\Config $vendorConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Templatestudio\Core\Model\Vendor\Config $vendorConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $backendConfig,
            $inboxFactory,
            $curlFactory,
            $deploymentConfig,
            $productMetadata,
            $urlBuilder,
            $resource,
            $resourceCollection,
            $data
        );
        $this->vendorConfig = $vendorConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedUrl()
    {
        if (null === $this->_feedUrl) {
            $feedUrl = $this->vendorConfig->getFeedUrl();

            if (! empty($feedUrl)) {
                $httpPath = $this->_backendConfig->isSetFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://';
                $this->_feedUrl = $httpPath . preg_replace('#^https?:\/\/#i', '', $feedUrl);
            }
        }

        return $this->_feedUrl;
    }

    /**
     * Check feed for modification
     *
     * @return $this
     */
    public function checkUpdate()
    {
        if ($this->getFrequency() + $this->getLastUpdate() > time()) {
            return $this;
        }

        $feedXml = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            $feedData = [];

            foreach ($feedXml->channel->item as $item) {
                $feedData[] = [
                    'severity' => (int) $item->severity,
                    'date_added' => date('Y-m-d H:i:s', strtotime((string) $item->pubDate)),
                    'title' => (string) $item->title,
                    'description' => (string) $item->description,
                    'url' => (string) $item->link
                ];
            }

            if ($feedData) {
                $this->_inboxFactory->create()->parse(array_reverse($feedData));
            }
        }
        $this->setLastUpdate();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFrequency()
    {
        return $this->_backendConfig->getValue(self::XML_FREQUENCY_PATH) * 3600;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load(self::CACHE_IDENTIFIER);
    }

    /**
     * {@inheritdoc}
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), self::CACHE_IDENTIFIER);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedData()
    {
        if (! $this->getFeedUrl()) {
            return false;
        }

        return parent::getFeedData();
    }
}
