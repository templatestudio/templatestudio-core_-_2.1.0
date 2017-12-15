<?php
/**
 * Copyright © 2017 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Block\Adminhtml\System\Config\Form\Field;

class Note extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Get element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        if (! $element->hasText()) {
            $element->setText($element->getData('field_config/text') ?: $element->getValue());
        }

        return parent::_getElementHtml($element);
    }
}
