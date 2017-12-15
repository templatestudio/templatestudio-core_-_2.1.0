<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Backend system config datetime field renderer
 */
class Notification extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Datetime formater
     *
     * @var DateTimeFormatterInterface
     */
    protected $dateTimeFormatter;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        DateTimeFormatterInterface $dateTimeFormatter,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * {@inheritdoc}
     * @see \Magento\Config\Block\System\Config\Form\Field::_getElementHtml()
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setValue($this->_cache->load(\Templatestudio\Core\Model\Feed::CACHE_IDENTIFIER));
        $format = $this->_localeDate->getDateTimeFormat(\IntlDateFormatter::MEDIUM);
        return $this->dateTimeFormatter->formatObject($this->_localeDate->date(intval($element->getValue())), $format);
    }
}