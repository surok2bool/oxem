<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractController
{

    public function index(Request $request)
    {
        $data = $request->getContent();

        $response = new Response();
        $response->setStatusCode(200);

        $responseBody = [
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiController.php',
            'error' => false
        ];

        $response->setContent(json_encode($responseBody));

        return $response;
    }
}
