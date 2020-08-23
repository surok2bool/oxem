<?php


namespace App\Service;


use App\Repository\CategoryRepository;

class CategoryManager
{

    /**
     * @var CategoryRepository $categoryRepository
     */
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function setCategories($categories)
    {
        $categories = json_decode($categories, true);

        /**
         * Дабы не делать запросов в цикле, соберем все имеющиеся externalId в файле, запросим все категории
         * по этим id из базы, потом снова пройдемся в цикле и узнаем, какие категории надо создать.
         */
        $externalIds = [];

        foreach ($categories as $category) {
            if (!empty($category['external_id']) && is_int($category['external_id'])) {
                $externalIds[] = $category['external_id'];
            } else {
                continue;
            }
        }

        if (!empty($externalIds)) {
            $categoriesObjects = $this->categoryRepository->findAllByExternalIds($externalIds);
            $existingCategoriesExternalIds = [];
            foreach ($categoriesObjects as $category) {
                $existingCategoriesExternalIds[] = (int) $category->getExternalId();
            }

            $externalIdsToUpdate = array_intersect($externalIds, $existingCategoriesExternalIds);
            $externalIdsToCreate = array_diff($externalIds, $existingCategoriesExternalIds);
        }

        return 'some message';
    }

    public function createCategories($externalIds)
    {

    }

    public function updateCategories($externalIds)
    {

    }
}