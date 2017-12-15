<?php
/**
 * Copyright © 2017 Templatestudio UK. All rights reserved.
 */

namespace Templatestudio\Core\Model;

interface LicenseInterface
{

    /**#@+
     * License statuses
     */
    const STATUS_INVALID = 0;
    const STATUS_ACTIVE = 1;
    /**#@-*/

    /**
     * Load license by module name
     *
     * @param string $module
     * @return bool|string
     */
    public function load($module);

    /**
     * Load license by class name
     *
     * @param string $className
     * @return bool|string
     */
    public function loadByClassName($className);

    /**
     * License status
     *
     * @param string $moduleName
     *
     * @return true|string
     */
    public function getStatus($moduleName);

    /**
     * Retrieve license
     * 
     * @return string
     */
    public function getLicense();

    /**
     * Get vendor
     *
     * @return string
     */
    public function getVendor();

    /**
     * Retrieve vendor domain name
     *
     * @return string
     */
    public function getVendorDomainName();
}
