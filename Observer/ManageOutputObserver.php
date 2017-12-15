<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Observer;

use Templatestudio\Core\Model\LicenseFactory;
use Templatestudio\Core\App\Module\ModuleListInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ManageOutputObserver implements ObserverInterface
{

    /**
     * License factory
     * 
     * @var null|LicenseFactory
     */
    protected $licenseFactory;

    /**
     * Module list
     * 
     * @var null|ModuleListInterface
     */
    protected $moduleList;

    /**
     * Constructor
     * 
     * @param LicenseFactory $licenseFactory
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        LicenseFactory $licenseFactory,
        ModuleListInterface $moduleList
    ) {
        $this->licenseFactory = $licenseFactory;
        $this->moduleList = $moduleList;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventObserver $observer)
    {
        $layout = $observer->getEvent()->getLayout();
        $name = $observer->getEvent()->getElementName();

        if ($name) {
            $block = $layout->getBlock($name);

            if (! ($block instanceof \Magento\Framework\View\Element\AbstractBlock)) {
                return $this;
            }

            $modules = $this->getModules($block);
            foreach ($modules as $moduleName) {
                if (true === $this->licenseFactory->create()->getStatus($moduleName)) {
                    continue;
                }

                $observer->getEvent()->getTransport()->setData('output', '');

                break;
            }
        }

        return $this;
    }

    /**
     * Retrieve modules
     * 
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return string[]
     */
    protected function getModules(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        $classes = array_merge([get_class($block)], class_parents($block, false) ?: []);

        $modules = [];
        foreach($classes as $class) {
            $moduleName = $block->extractModuleName($class);
            $vendor = substr($moduleName, 0, strpos($moduleName, '_'));

            if ($vendor !== $this->moduleList->getVendor()) {
                continue;
            }

            if ( ! in_array($moduleName, $modules)) {
                $modules[] = $moduleName;
            }
        }

        return array_unique($modules);
    }
}
