<?php

namespace Commercepundit\Gallery\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Commercepundit_Gallery::save';
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Commercepundit\Gallery\Model\GalleryFactory
     */
    protected $galleryFactory;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * Edit constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param \Commercepundit\Gallery\Model\GalleryFactory $galleryFactory
     * @param \Magento\Backend\Model\Session $session
     */
    public function __construct(Action\Context $context, PageFactory $resultPageFactory, Registry $registry, \Commercepundit\Gallery\Model\GalleryFactory $galleryFactory, \Magento\Backend\Model\Session $session)
    {
        $this->_coreRegistry = $registry;
        $this->session = $session;
        $this->resultPageFactory = $resultPageFactory;
        $this->galleryFactory = $galleryFactory;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * @return mixed
     */
    protected function _initAction()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Commercepundit_Gallery::gallery')
            ->addBreadcrumb(__('Gallery'), __('Gallery'))
            ->addBreadcrumb(__('Add New Gallery Image'), __('Add New Gallery Image'));
        return $resultPage;
    }

    /**
     * Edit Gallery Image
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->galleryFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This Gallery Image no longer exists.'));
                /** \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }
        
        $data = $this->session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('gallery', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Grid') : __('New Grid'),
            $id ? __('Edit Grid') : __('New Grid')
        );
        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend(__("Edit %1", $this->_coreRegistry->registry('gallery')->getName()));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('Add New Gallery Image'));
        }

        return $resultPage;
    }
}
