<?php
/**
 * Copyright © 2017 Templatestudio UK. All rights reserved.
 */

namespace Templatestudio\Core\App\Module;

interface ModuleListInterface extends \Magento\Framework\Module\ModuleListInterface
{

    /**
     * Retrieve vendor
     *
     * @return string
     */
    public function getVendor();

    /**
     * Set vendor
     *
     * @param string $vendor
     * @return $this
     */
    public function setVendor($vendor);
}
