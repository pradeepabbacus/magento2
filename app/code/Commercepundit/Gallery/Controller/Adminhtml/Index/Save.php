<?php

namespace Commercepundit\Gallery\Controller\Adminhtml\Index;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $cacheTypeList;
    /**
     * @var \Magento\Backend\Helper\Js
     */
    protected $jsHelper;

    const ADMIN_RESOURCE = 'Commercepundit_Gallery::save';
    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $adapterFactory;
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploader;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
    /**
     * @var \Magento\Backend\Model\View\Result\Redirect
     */
    protected $resultRedirectFactory;
    /**
     * @var \Commercepundit\Gallery\Model\GalleryFactory
     */
    protected $galleryFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var
     */
    protected $_directory;
    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $_imageFactory;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Backend\Helper\Js $jsHelper
     * @param \Magento\Framework\Image\AdapterFactory $adapterFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploader
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Image\AdapterFactory $imageFactory
     * @param \Magento\Backend\Model\View\Result\Redirect $resultRedirect
     * @param \Commercepundit\Gallery\Model\GalleryFactory $galleryFactory
     * @param \Magento\Backend\Model\Session $session
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\Image\AdapterFactory $adapterFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\Backend\Model\View\Result\Redirect $resultRedirect,
        \Commercepundit\Gallery\Model\GalleryFactory $galleryFactory,
        \Magento\Backend\Model\Session $session
    ) {
        $this->_storeManager = $storeManager;
        $this->_directory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_imageFactory = $imageFactory;
        $this->resultRedirectFactory = $resultRedirect;
        $this->galleryFactory = $galleryFactory;
        $this->adapterFactory = $adapterFactory;
        $this->session = $session;
        $this->uploader = $uploader;
        $this->filesystem = $filesystem;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
        $this->jsHelper = $jsHelper;
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
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
       
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            if (isset($_FILES['galleryimage']['name']) && strlen($_FILES['galleryimage']['name'])) {
                
                //Save image upload
                try {
                    $base_media_path = 'gallery/images/';
                    $uploader = $this->uploader->create(
                        ['fileId' => 'galleryimage']
                    );
                    $uploader->setAllowedExtensions(['jpg', 'jpeg','png']);
                    $uploader->setAllowRenameFiles(true);
                    $imageAdapter = $this->adapterFactory->create();
                    $uploader->addValidateCallback('galleryimage', $imageAdapter, 'validateUploadFile');
                    $uploader->setAllowRenameFiles(true);
                    $mediaDirectory = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                        
                    $result = $uploader->save(
                        $mediaDirectory->getAbsolutePath($base_media_path)
                    );
                    $data['galleryimage'] = $base_media_path.$result['name'];

                   
                } catch (\Exception $e) {
                    if ($e->getCode() == 0) {
                        $this->messageManager->addError($e->getMessage());
                    }
                }
            } else {
                if (isset($data['galleryimage']) && isset($data['galleryimage']['value'])) {
                    if (isset($data['galleryimage']['delete'])) {
                        $data['galleryimage'] = null;
                        $data['delete_image'] = true;
                    } elseif (isset($data['galleryimage']['value'])) {
                        $data['galleryimage'] = $data['galleryimage']['value'];
                    } else {
                        $data['galleryimage'] = null;
                    }
                }
            }

            
            $model = $this->galleryFactory->create();
            $id = $this->getRequest()->getParam('gallery_id');


            if ($id) {
                $model->load($id);
                $data['updated_date']=time();
            }
            $model->setData($data);
            try {
                $model->save();

                $this->cacheTypeList->invalidate('full_page');
                $this->messageManager->addSuccess(__('You saved this Data.'));
                $this->session->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
