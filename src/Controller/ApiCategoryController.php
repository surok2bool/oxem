<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Compiler\ResolveBindingsPass;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        $name = $data['name'] ?? '';
        $externalId = $data['externalId'] ?? '';

        $category->setName($name);
        $category->setExternalId($externalId);

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

        $response = new JsonResponse();
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

    /**
     * @param string $id
     * @return Response
     * @throws \Exception
     */
    public function getProductsInCategory(string $id): Response
    {
        $result = [
            'success' => false,
        ];

        try {
            $category = $this->categoryRepository->findById($id);
            $products = $category->getProducts();

            if (!$products->isEmpty()) {
                $result['success'] = true;
                /**
                 * @var Product $product
                 */
                foreach ($products->getIterator() as $product) {
                    $result['payload'][] = [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'description' => $product->getDescription(),
                        'dateCreate' => $product->getDateCreate(),
                        'price' => $product->getPrice(),
                        'quantity' => $product->getStock(),
                        'externalId' => $product->getExternalId(),
                        'categories' => $product->getCategoriesIds()
                    ];
                }
            }

        } catch (EntityNotFoundException $e) {
            $result['errors'][] = $e->getMessage();
        }


        $response = new JsonResponse();
        $response->setContent(json_encode($result));

        return $response;
    }

    /**
     * @return Response
     */
    public function getAll(): Response
    {
        $result = [
            'success' => false
        ];

        $categories = $this->categoryRepository->findAll();

        if (empty($categories)) {
            $result['error'][] = 'Categories not found';
        } else {
            $result['success'] = true;
            foreach ($categories as $category) {
                $result['payload'][] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'externalId' => $category->getExternalId(),
                ];
            }
        }

        return new JsonResponse($result);
    }

    /**
     * @param string $id
     * @return Response
     */
    public function delete($id): Response
    {
        $result = [
            'success' => false
        ];

        try {
            $product = $this->categoryRepository->findById((int) $id);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
            $result['success'] = true;
        } catch (EntityNotFoundException $e) {
            /**
             * Можно не обрабатывать, поскольку объект все равно хотят удалить
             */
        } catch (ForeignKeyConstraintViolationException $ex) {
            /**
             * Тут перехватываем исключение, если удаляемая категория является родительской для другой
             */
            $result['error'][] = 'Данная категория является родительской и не может быть удалена';
        }

        return new JsonResponse($result);
    }
}
