<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Config XML identifiers
     */
    const SECTION_ID = 'templatestudio';
    const GROUP_ID = 'extensions';

    /**
     * Retrieve helper module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_getModuleName();
    }

    /**
     * Retrieve path
     *
     * @param null|string $module
     * @return string
     */
    static public function getLicenseConfigPath($module = null)
    {
        return chop(implode('/', [
            self::SECTION_ID,
            self::GROUP_ID,
            $module
        ]), '/');
    }
}
