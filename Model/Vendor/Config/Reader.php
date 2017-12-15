<?php
/**
 * Copyright Â© 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Model\Vendor\Config;

class Reader extends \Magento\Framework\Config\Reader\Filesystem
{

    /**
     * Constructor
     *
     * @param \Magento\Framework\Config\FileResolverInterface $fileResolver
     * @param Converter $converter
     * @param SchemaLocator $schemaLocator
     * @param \Magento\Framework\Config\ValidationStateInterface $validationState
     */
    public function __construct(
        \Magento\Framework\Config\FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        \Magento\Framework\Config\ValidationStateInterface $validationState
    ) {
        parent::__construct($fileResolver, $converter, $schemaLocator, $validationState, 'vendor.xml');
    }
}