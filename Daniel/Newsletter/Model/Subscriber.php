<?php
/**
 * @category    Daniel
 * @package     Daniel_Newsletter
 */

namespace Daniel\Newsletter\Model;

use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Newsletter\Helper\Data;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Subscriber model
 */
class Subscriber extends \Magento\Newsletter\Model\Subscriber
{

    const XML_PATH_DANIEL_NEWSLETTER_RULE_ID = 'daniel_newsletter/daniel_subscribe/rule_id';

    /**
     * @var Collection _ruleCollection
     */
    protected $_ruleCollection;

    /**
     * @var \Magento\Checkout\Model\Session $_checkoutSession
     */
    protected $_checkoutSession;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Data $newsletterData
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param StateInterface $inlineTranslation
     * @param Collection $ruleCollection
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DateTime|null $dateTime
     * @param CustomerInterfaceFactory|null $customerFactory
     * @param DataObjectHelper|null $dataObjectHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $newsletterData,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        StateInterface $inlineTranslation,
        Collection $ruleCollection,
        \Magento\Checkout\Model\Session $checkoutSession,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DateTime $dateTime = null,
        CustomerInterfaceFactory $customerFactory = null,
        DataObjectHelper $dataObjectHelper = null
    )
    {
        parent::__construct(
            $context,
            $registry,
            $newsletterData,
            $scopeConfig,
            $transportBuilder,
            $storeManager,
            $customerSession,
            $customerRepository,
            $customerAccountManagement,
            $inlineTranslation,
            $resource,
            $resourceCollection,
            $data,
            $dateTime,
            $customerFactory,
            $dataObjectHelper
        );

        $this->_ruleCollection = $ruleCollection;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @return bool|Coupon|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function getCouponCode()
    {
        if ($this->hasData('coupon_code')) {
            return $this->getData('coupon_code');
        }

        $ruleId = $this->_scopeConfig->getValue(
            self::XML_PATH_DANIEL_NEWSLETTER_RULE_ID,
            ScopeInterface::SCOPE_STORE
        );

        if (!$ruleId) {
            return false;
        }

        /** @var Rule $salesRule */
        $salesRule = $this->_ruleCollection
            ->addFieldToFilter('rule_id', $ruleId)
            ->addFilterToMap('rule_id', 'main_table.rule_id')
            ->getFirstItem();

        $couponCode = $salesRule->acquireCoupon()->getCode();
        $this->setData('coupon_code', $couponCode);

        $this->_checkoutSession
            ->getQuote()
            ->setCouponCode($couponCode)
            ->collectTotals()
            ->save();

        return $this->getData('coupon_code');
    }
}
