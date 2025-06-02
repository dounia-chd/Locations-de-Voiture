<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{
    #[Route('/uploads/cars/{filename}', name: 'app_car_image')]
    public function carImage(string $filename): Response
    {
        $path = $this->getParameter('cars_directory') . '/' . $filename;
        
        if (!file_exists($path)) {
            throw $this->createNotFoundException('Image not found');
        }

        return new Response(
            file_get_contents($path),
            Response::HTTP_OK,
            ['Content-Type' => 'image/jpeg']
        );
    }
} 