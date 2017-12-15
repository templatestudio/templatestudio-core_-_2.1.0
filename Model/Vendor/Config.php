<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Model\Vendor;

// use Magento\Framework\DataObject;

/**
 * Vendor config
 *
 * @author Template Studio UK Team <sales@templatestudio.com>
 */
class Config extends \Magento\Framework\Config\Data
{

    /**
     * Default Vendor URL
     */
    const VENDOR_URL = 'http://www.templatestudio.com/';

    /**
     * Constructor
     *
     * @param \Templatestudio\Core\Model\Vendor\Config\Reader $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string $cacheId
     */
    public function __construct(
        \Templatestudio\Core\Model\Vendor\Config\Reader $reader,
        \Magento\Framework\Config\CacheInterface $cache,
        $cacheId = 'templatestudio_vendor'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Retrieve vendor/organization name
     *
     * @return string
     */
    public function getVendor()
    {
        return $this->get('org');
    }

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        return $this->getUrl('feed');
    }

    /**
     * Retrieve license check url
     *
     * @return string
     */
    public function getLicenseUrl()
    {
        return $this->getUrl('license');
    }

    /**
     * Retrieve quote url
     *
     * @return string
     */
    public function getQuoteUrl()
    {
        return $this->getUrl('quote');
    }

    /**
     * Retrieve vendor url
     *
     * @return string
     */
    public function getVendorUrl()
    {
        return $this->getUrl('vendor');
    }

    /**
     * Retrieve url
     *
     * @param string $type Optional
     * @return array|string
     */
    public function getUrl($type = null)
    {
        $path = 'url';

        if (null !== $type) {
            $path .= '/' . trim(strval($type), ' /');
        }

        return $this->get($path, self::VENDOR_URL);
    }
}