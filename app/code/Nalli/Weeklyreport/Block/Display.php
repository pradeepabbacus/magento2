<?php
namespace Nalli\Weeklyreport\Block;

use Magento\Framework\Url\EncoderInterface;

class Display extends \Magento\Framework\View\Element\Template
{
    protected $customerSession;

    /**
     * @var EncoderInterface
     */
    protected $urlEncode;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Nalli\Weeklyreport\Helper\Data $helperdata
     * @param \Magento\Framework\Data\Form\FormKey $formkey
     * @param \Magento\Framework\App\Response\Http $httpredirect
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param EncoderInterface $urlEncode
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Nalli\Weeklyreport\Helper\Data $helperdata,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Framework\App\Response\Http $httpredirect,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        EncoderInterface $urlEncode,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->helperdata = $helperdata;
        $this->formkey = $formkey;
        $this->httpredirect = $httpredirect;
        $this->storeManager = $storeManager;
        $this->urlEncode = $urlEncode;
    }

    /**
     * Get customer session
     * @return boolean
     */
      
    public function isCustomerLoggedIn()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerSession->getCustomer();
            return $customer;
        } else {
            return false;
        }
    }

    /**
     * Get Config value
     * @return string
     */

    public function getConfigValue($config_path)
    {
        return $this->helperdata->getConfig($config_path);
    }

    /**
     * Get Formkey value
     * @param string
     */

    public function getFormkeyValue()
    {
        return $this->formkey->getFormKey();
    }

    /**
     * Get EncodeUrl
     * @param $url
     * @return string
     */

    public function getEncodeUrl($url)
    {
        return $this->urlEncode->encode($url);
    }

    /**
     * Get Redirect Url
     * @return string
     */

    public function getRedirectUrl()
    {
        // $resultRedirect = $this->resultRedirectFactory->create();
        $httpredirect = $this->httpredirect->setRedirect('/eventuser/noroute');
        return $httpredirect;
    }

    /**
     * Redirect to Login Page
     * @return string
     */

    public function getRedirectLogin()
    {
        $referer = $this->getEncodeUrl($this->_storeManager->getStore()->getBaseUrl().'weeklyreport/index/display');
        $redirectlogin = $this->httpredirect->setRedirect('/customer/account/login', ['referer' => $referer]);
        return $redirectlogin;
    }
}
