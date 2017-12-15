<?php
/**
 * Copyright © 2017 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Plugin\Config\Structure\Element\Group;

use Templatestudio\Core\Helper\Data as CoreHelper;
use Magento\Config\Model\Config\Structure\Element\Group as ElementGroup;

class Extension
{

    /**
     * Vendor config
     *
     * @var null|\Templatestudio\Core\Model\Vendor\Config
     */
    protected $vendorConfig;

    /**
     * Templatestudio module list
     *
     * @var null|\Templatestudio\Core\App\Module\ModuleList
     */
    protected $moduleList;

    /**
     * Extension model factory
     *
     * @var null|\Templatestudio\Core\Model\ExtensionFactory
     */
    protected $extensionFactory;

    /**
     * Module conflict checker
     *
     * @var null|\Magento\Framework\Module\ConflictChecker $conflictChecker
     */
    protected $conflictChecker;

    /**
     * Escaper
     *
     * @var null|\Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * Core helper
     *
     * @var null|\Templatestudio\Core\Helper\Data
     */
    protected $coreHelper;

    /**
     * Modules data
     *
     * @var null|array
     */
    protected $modulesData;

    /**
     * Constructor
     *
     * @param \Templatestudio\Core\Model\Vendor\Config $vendorConfig
     * @param \Templatestudio\Core\App\Module\ModuleList $moduleList
     * @param \Templatestudio\Core\Model\ExtensionFactory $extensionFactory
     * @param \Magento\Framework\Module\ConflictChecker $conflictChecker
     * @param \Magento\Framework\Escaper $escaper
     * @param \Templatestudio\Core\Helper\Data $coreHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Templatestudio\Core\Model\Vendor\Config $vendorConfig,
        \Templatestudio\Core\App\Module\ModuleList $moduleList,
        \Templatestudio\Core\Model\ExtensionFactory $extensionFactory,
        \Magento\Framework\Module\ConflictChecker $conflictChecker,
        \Magento\Framework\Escaper $escaper,
        \Templatestudio\Core\Helper\Data $coreHelper
    ) {
        $this->vendorConfig = $vendorConfig;
        $this->moduleList = $moduleList;
        $this->extensionFactory = $extensionFactory;
        $this->conflictChecker = $conflictChecker;
        $this->coreHelper = $coreHelper;
        $this->escaper = $escaper;
    }

    /**
     * Add module fields
     *
     * @param ElementGroup $subject
     * @param callable $proceed
     * @param array $data
     * @param $scope
     * @return mixed
     */
    public function aroundSetData(
        ElementGroup $subject,
        callable $proceed,
        array $data,
        $scope
    ) {
        if (CoreHelper::SECTION_ID === $data['path'] and CoreHelper::GROUP_ID === $data['id']) {
            $extensionFields = $this->getExtensionConfigFields();

            if(! empty($extensionFields)) {
                if (! array_key_exists('children', $data)) {
                    $data['children'] = $extensionFields;
                } else {
                    $data['children'] += $extensionFields;
                }
            }
        }

        return $proceed($data, $scope);
    }

    /**
     * Retrive extension config fields fields
     *
     * @return array
     */
    protected function getExtensionConfigFields()
    {
        $extensionFields = [];
        $modules = $this->moduleList->getAll();
        $coreModule = $this->coreHelper->getModuleName();
        $modulesData = $this->getModulesData();

        if (! empty($modules)) {
            $conflicts = $this->conflictChecker->checkConflictsWhenEnableModules(
                $this->moduleList->getNames()
            );

            $sortOrder = 0;
            foreach ($modules as $moduleConfig) {
                $moduleName = $moduleConfig['name'];
                $requireLicense = false;
                $version = null;

                if ($coreModule === $moduleName) {
                    continue;
                }

                $label = ltrim(strstr($moduleName, '_'), '_');
                if (! empty($moduleConfig['setup_version'])) {
                    $version = $moduleConfig['setup_version'];
                }

                $class = 'module-success';
                $url = $this->getVendorUrl();
                $tooltip = null;

                if (is_array($modulesData) and array_key_exists($moduleName, $modulesData)) {
                    $module = $modulesData[$moduleName];

                    if (! empty($module['version'])
                        and version_compare($version, $module['version'], 'lt')) {
                        $class = 'module-notice';
                        $tooltip = __('Update available');
                    }

                    if (! empty($module['display_name'])) {
                        $label = $module['display_name'];
                    } elseif (! empty($module['name'])) {
                        $label = $module['name'];
                    }

                    if (! empty($module['url'])) {
                        $url = $module['url'];
                    }

                    if (isset($module['require_license'])) {
                        $requireLicense = (bool) $module['require_license'];
                    }

                    unset($module);
                }

                $label = '<a href="' . $url . '" '
                    . 'title="'. $this->escaper->escapeHtml($label)
                    . '" onclick="this.target=\'_blank\'">' . $label . '</a>';

                if (array_key_exists($moduleName, $conflicts)) {
                    $class = 'module-warning';
                    $tooltip = implode("\n", $conflicts[$moduleName]);
                }

                if ($version) {
                    $label .= ' (' . $version . ')';
                }

                $extensionFields[$moduleName] = [
                    'id' => $moduleName,
                    'type' => $requireLicense ? 'text' : 'note',
                    'sortOrder' => ($sortOrder * 10),
                    'showInDefault' => 1,
                    'showInWebsite' => 0,
                    'showInStore' => 0,
                    'label' => '<i class="module ' . $class . '"></i>' . $label,
                    '_elementType' => 'field',
                    'path' => CoreHelper::getLicenseConfigPath(),
                    'tooltip' => $tooltip
                ];

                if (! $requireLicense) {
                    $extensionFields[$moduleName]['text'] = __('Doesn\'t require license')
                        ->render();
                    $extensionFields[$moduleName]['frontend_model'] = $this->getNoteFrontendModel();
                }

                $sortOrder++;
            }
        }

        if (! count($extensionFields)) {
            $extensionFields['no-extensions'] = [
                'id' => 'no-extensions',
                'type' => 'note',
                'sortOrder' => 0,
                'showInDefault' => 1,
                'showInWebsite' => 0,
                'showInStore' => 0,
                'label' => null,
                '_elementType' => 'field',
                'path' => CoreHelper::getLicenseConfigPath(),
                'frontend_model' => $this->getNoteFrontendModel(),
                'text' => __('There are no Template Studio extensions installed.')
            ];
        }

        return $extensionFields;
    }

    /**
     * Retrieve vendor/developer URL
     *
     * @return string
     */
    protected function getVendorUrl()
    {
        return $this->vendorConfig->getVendorUrl();
    }

    /**
     * Retrieve modules data
     *
     * @return array
     */
    protected function getModulesData()
    {
        if (null === $this->modulesData) {
            $extensionModel = $this->extensionFactory->create();
            $extensionModel->checkUpdate();

            $this->modulesData = $extensionModel->getModulesData();
        }

        return $this->modulesData;
    }

    /**
     * Retrieve frontend model
     *
     * @return string
     */
    public function getNoteFrontendModel()
    {
        return 'Templatestudio\Core\Block\Adminhtml\System\Config\Form\Field\Note';
    }
}
