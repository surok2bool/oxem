<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiProductController extends AbstractController
{
    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var ProductRepository $productRepository
     */
    private $productRepository;

    /**
     * @var CategoryRepository $categoryRepository
     */
    private $categoryRepository;

    /**
     * ApiController constructor.
     * @param ValidatorInterface $validator
     * @param ProductRepository $productRepository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    )
    {
        $this->validator = $validator;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function createProduct(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $entityManager = $this->getDoctrine()->getManager();

        $product = new Product();

        /**
         * Результирующий массив, в котором будем собирать все ошибки и результат
         */
        $result = [
            'success' => false
        ];

        /**
         * Если переданы id несуществующих категорий, проставляем только существующие и не уведомляем пользователя
         */
        if (!empty($data['categories']) && is_array($data['categories'])) {
            $categories = $this->getCategories($data['categories']);
            foreach ($categories as $category) {
                $product->addCategory($category);
            }
        }

        $name = $data['name'] ?? '';
        $description = $data['description'] ?? null;
        $externalId = $data['externalId'] ?? null;

        $product->setName($name);

        $product->setDescription($description);
        $product->setExternalId($externalId);

        /**
         * Для цены используется тип float, для большего удобства пользования предположим, что
         * пользователь может передать цену как строку, поэтому сначала попытаемся привести значение
         * к float, в случае неудачи - проставим цену как 0.
         */
        if (!empty($data['price']) && settype($data['price'], 'float')) {
            $price = round($data['price'], 2);
            $product->setPrice($price);
        } else {
            $product->setPrice(0.00);
        }

        $stock = (!empty($data['quantity']) && settype($data['quantity'], 'integer'))
            ? $data['quantity']
            : 0;
        $product->setStock($stock);
        $product->setDateCreate();

        $errors = $this->validator->validate($product);

        if ($errors->count() > 0 || !empty($result['errors'])) {
            foreach ($errors as $error) {
                $result['errors'][] = $error->getMessage();
            }
        } else {
            $entityManager->persist($product);
            $entityManager->flush();
        }

        if (!empty($product->getId())) {
            $result['success'] = true;
            $result['payload']['id'] = $product->getId();
        } else {
            $result['errors'][] = 'Failed to create product';
        }

        $response = new JsonResponse();
        $response->setStatusCode(200);

        $response->setContent(json_encode($result));

        return $response;
    }

    /**
     * @param array $categoriesIds
     * @return Category[]
     */
    private function getCategories(array $categoriesIds): array
    {
        return $this->categoryRepository->findByIds($categoriesIds);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function getList(Request $request): Response
    {
        $offset = (int) $request->get('offset');

        switch ($request->get('sortBy')) {
            case 'dateCreate':
                $sortBy = 'dateCreate';
                break;
            case 'price':
                $sortBy = 'price';
                break;
            default:
                $sortBy = 'dateCreate';
        }

        switch ($request->get('sort')) {
            case 'ASC':
                $sort = 'ASC';
                break;
            case 'DESC':
                $sort = 'DESC';
                break;
            default:
                $sort = 'DESC';
        }

        $products = $this->productRepository->getAll($offset, $sortBy, $sort);

        /**
         * Если товары не найдены, возвращаем пустой массив
         * Так планировалось...
         */
        $result = [
            'success' => true,
            'payload' => []
        ];

        /**
         * Эту логику надо бы вынести в отдельный метод, но я катастрофически не успеваю
         */
        foreach ($products as $key => $product) {
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

        return new JsonResponse($result);
    }

    /**
     * @param string $id
     * @return Response
     */
    public function getById($id): Response
    {
        try {
            $product = $this->productRepository->getById((int) $id);
            $result['success'] = true;

            /**
             * По неясной для меня причине, именно в данном методе вызов $product->getCategoriesIds()
             * приводит к 502 ошибке, хотя в методе getList отрабатывает нормально
             */
            $result['payload'][] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'dateCreate' => $product->getDateCreate(),
                'price' => $product->getPrice(),
                'quantity' => $product->getStock(),
                'externalId' => $product->getExternalId(),
//                'categories' => $product->getCategoriesIds()
            ];

        } catch (EntityNotFoundException $e) {
            $result['success'] = false;
            $result['error'][] = $e->getMessage();
        }

        return new JsonResponse($result);
    }

    /**
     * @param string $id
     * @return JsonResponse
     */
    public function deleteProduct($id): JsonResponse
    {
        $result = [
            'success' => true
        ];

        try {
            $product = $this->productRepository->getById((int) $id);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        } catch (EntityNotFoundException $e) {
            /**
             * Можно не обрабатывать, поскольку объект все равно хотят удалить
             */
        }

        return new JsonResponse($result);
    }
}
