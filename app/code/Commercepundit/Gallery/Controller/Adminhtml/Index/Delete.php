<?php

namespace Commercepundit\Gallery\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * @var \Commercepundit\Gallery\Model\GalleryFactory
     */
    protected $galleryFactory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $loggerInterface;

    const ADMIN_RESOURCE = 'Commercepundit_Gallery::event_delete';

    /**
     * Delete constructor.
     * @param Action\Context $context
     * @param \Commercepundit\Gallery\Model\GalleryFactory $galleryFactory
     * @param \Psr\Log\LoggerInterface $loggerInterface
     */
    public function __construct(
        Action\Context $context,
        \Commercepundit\Gallery\Model\GalleryFactory $galleryFactory,
        \Psr\Log\LoggerInterface $loggerInterface
    ) {
        $this->galleryFactory = $galleryFactory;
        $this->loggerInterface = $loggerInterface;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * execute function in delete logic
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {

                $model = $this->galleryFactory->create();
                $model->load($id);
                $model->delete();
                $this->_redirect('gallery/*/');
                $this->messageManager->addSuccess(__('Deleted Data successfully.'));
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete this record right now. Please review the log and try again.')
                );
                $this->loggerInterface->critical($e);
                $this->_redirect('gallery/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a record to delete.'));
        $this->_redirect('gallery/*/');
    }
}
