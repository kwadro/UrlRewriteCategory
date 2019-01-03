<?php
/**
 * Created by PhpStorm.
 * User: igor
 * Date: 31.12.2018
 * Time: 10:17
 */

namespace Kvadro\RegenerateUrl\Controller\Index;

use Magento\Framework\App\Action\Context as Context;


class setProductInstallation extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $ProductCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $StoreManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collecionCategoryFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        Context $context

    )
    {
        $this->_categoryId = '';
        $this->_request_path = '';
        $this->_target_path = '';

        $this->_test_mode = true;
        $this->_ProductCollectionFactory = $ProductCollectionFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $collecionCategoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->_storeManager = $StoreManager;
        $this->root_category_id = $this->_storeManager->getStore()->getRootCategoryId();
        $this->_store_id = 1;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->validateToken()) exit;

        $installation_category = $this->getCategoryByName($this->root_category_id, "Installation");
        $installation_category_id = [$installation_category->getData()[0]['entity_id']];
        $collection = $this->_ProductCollectionFactory->create();
        $collection->setFlag('has_stock_status_filter', true);
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $products = $collection->addCategoriesFilter(['in' => $installation_category_id]);
        foreach ($products as $product) {
            echo $product->getSku()." : ".$product->getName()."<br/>";
            try {
                $product->setStockData(['qty' => 1000, 'is_in_stock' => true]);
                $product->save();
            } catch (\Exception $e) {
                return 'error save :' . $product->getSku() . "<br/>";
            }
        }
    }


    public function validateToken()
    {
        $post = $this->getRequest()->getParams();
        if (!isset($post['token']) || $post['token'] !== '123456') {
            echo "<div style='text-align: center;padding:30px;margin-top:50px'> <h1 > Access error </h1></div>";
            return false;
        }
        return true;

    }

    protected function getCategoryByName($root_id, $category_name)
    {

        $post = $this->getRequest()->getParams();
        if (!isset($post['token']) || $post['token'] !== '123456') {
            echo "<div style='text-align: center;padding:30px;margin-top:50px'> <h1 > Access error </h1></div>";
            return false;
        }

        $root_category = $this->_categoryFactory->create()->load($root_id);
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addIdFilter($root_category->getChildren());
        $collection->addFieldToFilter('name', array('eq' => $category_name));
        $collection->getFirstItem();
        return $collection;
    }
}