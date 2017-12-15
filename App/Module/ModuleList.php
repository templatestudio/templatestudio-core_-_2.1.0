<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */

namespace Templatestudio\Core\App\Module;

use Magento\Framework\Module\ModuleList\Loader;

/**
 * A list of Templatestudio modules
 */
class ModuleList extends \Magento\Framework\Module\FullModuleList implements ModuleListInterface
{

    /**
     * Loader of module information from source code
     *
     * @var ModuleList\Loader
     */
    private $loader;

    /**
     * Enumeration of the module names
     *
     * @var string[]
     */
    private $data;

    /**
     * Vendor
     *
     * @var string
     */
    protected $vendor;

    /**
     * Constructor
     *
     * @param Loader $loader
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Retrieve all Templatestudio modules
     * 
     * @return string[]
     */
    public function getAll()
    {
        if (null === $this->data) {
            $data = $this->loader->load();

            $this->data = [];
            foreach ($data as $module) {
                if (strtok($module['name'], '_') == $this->getVendor()) {
                    $this->data[] = $module;
                }
            }
        }

        return $this->data;
    }

    /**
     * Retrieve vendor
     * 
     * @return string
     */
    public function getVendor()
    {
        if (null === $this->vendor) {
            $this->vendor = strtok(get_class($this), '\\');
        }

        return $this->vendor;
    }

    /**
     * Set vendor
     *
     * @param String $vendor
     * @return $this
     */
    public function setVendor($vendor)
    {
        $this->vendor = trim($vendor);

        return $this;
    }
}