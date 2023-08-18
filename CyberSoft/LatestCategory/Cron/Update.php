<?php
namespace CyberSoft\LatestCategory\Cron;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;

class Update
{
    protected $categoryFactory;
    protected $productFactory;
    protected $dateTime;
    protected $productCollectionFactory;

    public function __construct(
        CategoryFactory $categoryFactory,
        ProductFactory $productFactory,
        DateTime $dateTime,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->productFactory = $productFactory;
        $this->dateTime = $dateTime;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function execute()
    {
        $categoryId = 232; // Your category Id
        $category = $this->categoryFactory->create()->load($categoryId);
        $category->setPostedProducts([]); // Removes all old products added to your category
        $category->save();

        $now = $this->dateTime->timestamp();
        $dateStart = date('Y-m-d' . ' 00:00:00', strtotime('-2 days'));
        $dateEnd = date('Y-m-d' . ' 23:59:59', $now);

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->setVisibility([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);
        $productCollection->addAttributeToFilter('created_at', ['from' => $dateStart, 'to' => $dateEnd]);
        $productCollection->setOrder('created_at', 'desc');
        $productCollection->setPageSize(12);

        $categoryIds = [$categoryId];
        foreach ($productCollection as $product) {
            $categoryIds = array_merge($categoryIds, $product->getCategoryIds());
            $product->setCategoryIds($categoryIds);
            $product->save();
        }
    }
}
