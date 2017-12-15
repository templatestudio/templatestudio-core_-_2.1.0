<?php
/**
 * Copyright Â© 2016 Templatestudio UK. All rights reserved.
 */

namespace Templatestudio\Core\Block\Adminhtml\System\Config\Form\Fieldset\Modules;

class Templatestudio extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    /**
     * Vendor config
     * 
     * @var null|\Templatestudio\Core\Model\Vendor\Config
     */
    protected $vendorConfig;

    /**
     * Constructor
     * 
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Templatestudio\Core\Model\Vendor\Config $vendorConfig
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Templatestudio\Core\Model\Vendor\Config $vendorConfig,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->vendorConfig = $vendorConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getHeaderHtml($element)
    {
        $html = parent::_getHeaderHtml($element);

        $html .= '
        <a id="templatestudio-core-quote" href="'. $this->getQuoteUrl() . '"'.
            ' title="' . __('Get in touch') . '" target="_blank">
            <img src="' . $this->getViewFileUrl('Templatestudio_Core::images/templatestudio-quote.jpg') . '" '.
                'alt="' . __('Get in touch') . '" />
        </a>';

        $thead = '<thead><tr>'
            . '<td class="label"><strong>' . __('Module Name') . '</strong></td>'
            . '<td><strong>' . __('License') . '</strong></td>'
            . '<td></td>'
            . '</tr></thead>';

        return substr_replace($html, $thead, stripos($html, '<tbody>'), 0);
    }

    /**
     * Retrieve quote URL
     * 
     * @return string
     */
    protected function getQuoteUrl()
    {
        return $this->vendorConfig->getQuoteUrl();
    }
}
