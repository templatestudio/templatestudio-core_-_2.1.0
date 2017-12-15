<?php
/**
 * Copyright Â© 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Model\Vendor\Config;

use Magento\Framework\Module\Dir;

class SchemaLocator implements \Magento\Framework\Config\SchemaLocatorInterface
{

    /**
     * XSD Filename
     */
    const XSD_VENDOR = 'vendor.xsd';

    /**
     * Path to corresponding XSD file with validation rules for merged config
     *
     * @var string
     */
    private $schema;

    /**
     * Constructor
     * 
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     */
    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $this->schema = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Templatestudio_Core')
            . '/' . self::XSD_VENDOR;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerFileSchema()
    {
        return $this->schema;
    }

    /**
     * Retrieve XSD filename
     * 
     * @return string
     */
    static public function getVendorXsdFilename()
    {
        return self::XSD_VENDOR;
    }
}