<?php

namespace Stape\Gtm\Plugin;

use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicyFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Stape\Gtm\Model\ConfigProvider;

class CspObserverPlugin
{
    /**
     * @var ConfigProvider $configProvider
     */
    private $configProvider;

    /**
     * @var FetchPolicyFactory $fetchPolicyFactory
     */
    private $fetchPolicyFactory;

    /**
     * @var DynamicCollector $dynamicCollector
     */
    private $dynamicCollector;

    /**
     * Define class dependencies
     *
     * @param ConfigProvider $configProvider
     * @param FetchPolicyFactory $fetchPolicyFactory
     * @param DynamicCollector $dynamicCollector
     */
    public function __construct(
        ConfigProvider $configProvider,
        FetchPolicyFactory $fetchPolicyFactory,
        DynamicCollector $dynamicCollector
    ) {
        $this->configProvider = $configProvider;
        $this->fetchPolicyFactory = $fetchPolicyFactory;
        $this->dynamicCollector = $dynamicCollector;
    }

    /**
     * Adding custom domain to CSP policy
     *
     * @param ObserverInterface $subject
     * @param Observer $observer
     * @return array
     */
    public function beforeExecute(ObserverInterface $subject, $observer)
    {
        // phpcs:disable
        $customDomain = parse_url($this->configProvider->getCustomDomain() ?? '', PHP_URL_HOST);
        // phpcs:enable

        if (!$this->configProvider->isActive() || empty($customDomain)) {
            return [$observer];
        }

        $scriptPolicy = $this->fetchPolicyFactory->create([
            'id' => 'script-src',
            'hostSources' => [$customDomain],
            'schemeSources' => ['https'],
            'noneAllowed' => false,
            'selfAllowed' => true,
            'inlineAllowed' => true
        ]);

        $connectPolicy = $this->fetchPolicyFactory->create([
            'id' => 'connect-src',
            'hostSources' => [$customDomain],
            'schemeSources' => ['https'],
            'noneAllowed' => false,
            'selfAllowed' => true,
            'inlineAllowed' => true
        ]);

        $this->dynamicCollector->add($scriptPolicy);
        $this->dynamicCollector->add($connectPolicy);
        return [$observer];
    }
}
