<?php

namespace Commercepundit\Gallery\Block\Adminhtml\Gallery\Edit\Tab;

class Mainform extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $store;

    /**
     * Mainform constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * prepare form
     * @return mixed
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('gallery');
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('gallery_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('gallery'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('gallery_id', 'hidden', ['name' => 'gallery_id']);
        }
        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true
            ]
        );
 
        $fieldset->addField(
            'galleryimage',
            'image',
            [
                'name' => 'galleryimage',
                'label' => __('Gallery Image'),
                'note' => __('Allowed image types: jpg,png,jpeg'),
                'title' => __('Gallery Image'),
                'required'  => true
            ]
        );
        $fieldset->addField(
            'orderby',
            'text',
            [
                'name' => 'orderby',
                'label' => __('Order'),
                'title' => __('Order'),
                'required' => true
            ]
        );
          
        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'status',
                'options' => ['1' => __('Enable'), '0' => __('Disable')]
            ]
        );
        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    
    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Gallery');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Gallery');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
