<?php
namespace Nalli\Weeklyreport\Controller\Index;

class Customsoldproducts extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    protected $formKeyValidator;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magento\Framework\Filesystem\DirectoryList $dir
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_stockFilter = $stockFilter;
        $this->formKeyValidator = $formKeyValidator;
        $this->_dir = $dir;
        return parent::__construct($context);
    }

    private function validateSession()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if ($customerSession->isLoggedIn()) {
            $customeremail = $customerSession->getCustomer()->getEmail();
            $allowed_user = $objectManager->create('Nalli\Weeklyreport\Helper\Data')->getConfig('weeklyreport/general/allowed_users');
            $screenusers = array_map('trim', explode(',', $allowed_user));
            if (in_array($customeremail, $screenusers)) {
                return true;
            }
        }
        return false;
    }

    public function execute()
    {

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('*/*');
        }
        if (!$this->validateSession()) {
            print_r("You do not have access to this report");
            die;
        }
        
        $data = $this->getRequest()->getParams();
        
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        
        if ($data['report'] == 'detectuseragent') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->detectuseragent();
        }
        
        if (!isset($data['from']) || !isset($data['to'])) {
            print_r("Please enter a from and to date");
            die;
        }
        $start = $data['from'];
        $end = $data['to'];
                
        if ($data['report'] == 'customsoldproducts') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->customsoldproducts($start, $end);
        } elseif ($data['report'] == 'ordergiftmsg') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->ordergiftmsg($start, $end);
        } elseif ($data['report'] == 'customuploads') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->customuploads($start, $end);
        } elseif ($data['report'] == 'shoppingadsindia') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->shoppingadsindia($start, $end);
        } elseif ($data['report'] == 'shoppingadsusa') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->shoppingadsusa($start, $end);
        } elseif ($data['report'] == 'shoppingadscanada') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->shoppingadscanada($start, $end);
        } elseif ($data['report'] == 'accountssales') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->accountssales($start, $end);
        } elseif ($data['report'] == 'customabandoned') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->customabandoned($start, $end);
        } elseif ($data['report'] == 'ordertracking') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->ordertracking($start, $end);
        } elseif ($data['report'] == 'followedcategories') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->followedcategories();
        } elseif ($data['report'] == 'categorymaster') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->categorymaster();
        } elseif ($data['report'] == 'mothersdaycontest') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->mothersdaycontest();
        } elseif ($data['report'] == 'customwishlist') {
            $objectManager->create('Nalli\Weeklyreport\Helper\Data')->customwishlist($start, $end);
        } else {
            print_r("Report doesn't exist");
            die;
        }
    }
}
