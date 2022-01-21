<?php

namespace FireGento\FastSimpleImport\ResourceModel\Import\Category;

class Storage
{
    /**
     * Flag to not load collection more than one time
     *
     * @var bool
     */
    protected $_isCollectionLoaded = false;

    /**
     * Customer collection
     *
     * @var \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    protected $_categoryCollection;

    /**
     * Existing customers information. In form of:
     *
     * [customer email] => array(
     *    [website id 1] => customer_id 1,
     *    [website id 2] => customer_id 2,
     *           ...       =>     ...      ,
     *    [website id n] => customer_id n,
     * )
     *
     * @var array
     */
    protected $_customerIds = [];

    /**
     * Number of items to fetch from db in one query
     *
     * @var int
     */
    protected $_pageSize;

    /**
     * Collection by pages iterator
     *
     * @var \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIterator
     */
    protected $_byPagesIterator;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory $colIteratorFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory $colIteratorFactory,
        array $data = []
    ) {
        $this->_categoryCollection = isset(
            $data['customer_collection']
        ) ? $data['customer_collection'] : $collectionFactory->create();
        $this->_pageSize = isset($data['page_size']) ? $data['page_size'] : 0;
        $this->_byPagesIterator = isset(
            $data['collection_by_pages_iterator']
        ) ? $data['collection_by_pages_iterator'] : $colIteratorFactory->create();
    }

    /**
     * Load needed data from customer collection
     *
     * @return void
     */
    public function load()
    {
        if ($this->_isCollectionLoaded == false) {


            $collection = Mage::getResourceModel('catalog/category_collection')->addNameToResult();
            /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */

            foreach ($collection as $category) {
                /** @var $category Mage_Catalog_Model_Category */
                $structure = explode('/', $category->getPath());
                $pathSize = count($structure);
                if ($pathSize > 1) {
                    $path = [];
                    for ($i = 1; $i < $pathSize; $i++) {
                        $path[] = $collection->getItemById($structure[$i])->getName();
                    }
                    $rootCategoryName = array_shift($path);
                    if (!isset($this->_categoriesWithRoots[$rootCategoryName])) {
                        $this->_categoriesWithRoots[$rootCategoryName] = [];
                    }
                    $index = $this->_implodeEscaped('/', $path);

                    $this->_categoriesWithRoots[$rootCategoryName][$index] = [
                        'entity_id' => $category->getId(),
                        'path' => $category->getPath(),
                        'level' => $category->getLevel(),
                        'position' => $category->getPosition()
                    ];

//allow importing by ids.
                    if (!isset($this->_categoriesWithRoots[$structure[1]])) {
                        $this->_categoriesWithRoots[$structure[1]] = [];
                    }
                    $this->_categoriesWithRoots[$structure[1]][$category->getId()] =
                        $this->_categoriesWithRoots[$rootCategoryName][$index];
                }
            }


            $collection = clone $this->_categoryCollection;
            $collection->removeAttributeToSelect();
            $tableName = $collection->getResource()->getEntityTable();
            $collection->getSelect()->from($tableName, ['entity_id', 'website_id', 'email']);

            $this->_byPagesIterator->iterate(
                $this->_categoryCollection,
                $this->_pageSize,
                [[$this, 'addCustomer']]
            );

            $this->_isCollectionLoaded = true;
        }
    }

    /**
     * Add customer to array
     *
     * @param \Magento\Framework\DataObject|\Magento\Customer\Model\Customer $customer
     * @return $this
     */
    public function addCategory(\Magento\Framework\DataObject $customer)
    {
        $email = strtolower(trim($customer->getEmail()));
        if (!isset($this->_customerIds[$email])) {
            $this->_customerIds[$email] = [];
        }
        $this->_customerIds[$email][$customer->getWebsiteId()] = $customer->getId();

        return $this;
    }

    /**
     * Get customer id
     *
     * @param string $email
     * @param int $websiteId
     * @return bool|int
     */
    public function getCustomerId($email, $websiteId)
    {
        // lazy loading
        $this->load();

        if (isset($this->_customerIds[$email][$websiteId])) {
            return $this->_customerIds[$email][$websiteId];
        }

        return false;
    }
}
