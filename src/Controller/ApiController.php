<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiController extends AbstractController
{
    /**
     * @var ValidatorInterface $validator
     */
    private $validator;


    /**
     * ApiController constructor.
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
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

        try {
            $product->setProductData($data);
        } catch (EntityNotFoundException $e) {
            $result['errors'][] = $e->getMessage();
        }

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

        $response = new Response();
        $response->setStatusCode(200);

        $response->setContent(json_encode($result));

        return $response;
    }
}
