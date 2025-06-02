<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    /**
     * @Route("/uploads/cars/{filename}", name="app_car_image")
     */
    public function carImage(string $filename): Response
    {
        // Convertir les tirets en underscores pour correspondre aux noms de fichiers réels
        $filename = str_replace('-', '_', $filename);
        
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/cars/' . $filename;
        
        if (!file_exists($path)) {
            throw $this->createNotFoundException('Image not found');
        }

        $response = new Response(
            file_get_contents($path),
            Response::HTTP_OK,
            ['Content-Type' => 'image/jpeg']
        );

        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }
} 