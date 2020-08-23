<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiCategoryController extends AbstractController
{
    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * ApiController constructor.
     * @param ValidatorInterface $validator
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(ValidatorInterface $validator, CategoryRepository $categoryRepository)
    {
        $this->validator = $validator;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function createCategory(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $entityManager = $this->getDoctrine()->getManager();

        $category = new Category();

        /**
         * Результирующий массив, в котором будем собирать все ошибки и результат
         */
        $result = [
            'success' => false
        ];

        /**
         * Возможно, это не лучшая идея - размазывать так логику установки полей объекта (сначала проставить
         * одни поля, а потом отдельно установить другое поле. Не уверен, как лучше тут поступить.
         */
        $category->setCategoryData($data);

        if (!empty($data['parentId']) && is_int($data['parentId'])) {
            try {
                $parentCategory = $this->getParentCategory($data['parentId']);
                /**
                 * Либо же можно изменить массив, который мы передаем методу setCategoryData() и добавить
                 * в него объект, а уже внутри этого метода присвоить родительскую категорию, но что-то
                 * мне не нравится такой вариант
                 */
                $category->setParent($parentCategory);
            } catch (EntityNotFoundException $e) {
                $result['errors'][] = $e->getMessage();
            }
        }

        /**
         * Этот участок кода (что в продукте, что в категории) можно, в принципе, вынести в отдельный метод,
         * сделать единое место для валидации и обработки ошибок.
         * Поскольку я использую разные контроллеры - к примеру, в трейт, но меня время поджимает...
         */
        $errors = $this->validator->validate($category);

        if ($errors->count() > 0 || !empty($result['errors'])) {
            foreach ($errors as $error) {
                $result['errors'][] = $error->getMessage();
            }
        } else {
            $entityManager->persist($category);
            $entityManager->flush();
        }

        if (!empty($category->getId())) {
            $result['success'] = true;
            $result['payload']['id'] = $category->getId();
        } else {
            $result['errors'][] = 'Failed to create category';
        }

        $response = new Response();
        $response->setStatusCode(200);

        $response->setContent(json_encode($result));

        return $response;
    }

    /**
     * @param int $id
     * @return Category|null
     * @throws EntityNotFoundException
     */
    public function getParentCategory(int $id): ?Category
    {
        $parentCategory = $this->categoryRepository->find($id);

        if (is_null($parentCategory)) {
            throw new EntityNotFoundException('Parent category not found');
        }
        return $parentCategory;
    }
}