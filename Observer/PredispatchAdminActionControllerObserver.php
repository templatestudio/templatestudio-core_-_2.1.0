<?php
/**
 * Copyright © 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Notification observer
 */
class PredispatchAdminActionControllerObserver implements ObserverInterface
{

    /**
     * XML Config Path
     */
    const XML_PATH_TEMPLATESTUDIO_FEED_ENABLED = 'templatestudio/notification/enabled';

    /**
     * Feed factory
     *
     * @var \Templatestudio\Core\Model\FeedFactory
     */
    protected $feedFactory;

    /**
     * Extension model factory
     *
     * @var \Templatestudio\Core\Model\ExtensionFactory
     */
    protected $extensionFactory;

    /**
     * Backend Auth session model
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * Backend configuration
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $backendConfig;

    /**
     * Constructor
     *
     * @param \Templatestudio\Core\Model\FeedFactory $feedFactory
     * @param \Templatestudio\Core\Model\ExtensionFactory $extensionFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     */
    public function __construct(
        \Templatestudio\Core\Model\FeedFactory $feedFactory,
        \Templatestudio\Core\Model\ExtensionFactory $extensionFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Backend\App\ConfigInterface $backendConfig
    ) {
        $this->feedFactory = $feedFactory;
        $this->extensionFactory = $extensionFactory;
        $this->backendAuthSession = $backendAuthSession;
        $this->backendConfig = $backendConfig;
    }

    /**
     * Predispath admin action controller
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* Check Feed */
        if ($this->backendAuthSession->isLoggedIn()
            and $this->backendConfig->isSetFlag(self::XML_PATH_TEMPLATESTUDIO_FEED_ENABLED)) {
            $feedModel = $this->feedFactory->create();
            $feedModel->checkUpdate();
        }

        /* Check extension/modules */
        $extensionModel = $this->extensionFactory->create();
        $extensionModel->checkUpdate();
    }
}