<?php

namespace App\Controller;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageController extends AbstractController
{
    #[Route('/upload', name: 'app_image', methods: ['POST'])]
    public function upload(Request $request, SluggerInterface $slugger, EntityManagerInterface $manager): JsonResponse
    {
        $image = $request->files->get('image');
        if ($image) {
            $originalFileName =  pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFileName = $slugger->slug($originalFileName);
            $newFilename = $safeFileName.'-'.uniqid().'.'.$image->guessExtension();
            if (!file_exists($this->getParameter('uploads_directory'))) {
                mkdir($this->getParameter('uploads_directory'), 0777, true);
            }
            try {
                $image->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
                file_put_contents($this->getParameter('log_directory') .
                    '/move_log', $newFilename . "\n", FILE_APPEND);
            } catch (FileException $e) {
                file_put_contents($this->getParameter('log_directory') .
                    '/move_error_log', $e . "\n", FILE_APPEND);
            }
            $image = new Image();
            $image->setName($newFilename);
            $manager->persist($image);
            $manager->flush();
            return $this->json([
                'message' => 'Image uploaded',
                'imgUrl' => "{$_SERVER['HTTP_HOST']}{$this->getParameter('public_uploads_directory')}/$newFilename",
                'success' => true,
            ]);
        }
        return $this->json([
            'message' => 'Image was not uploaded',
            'success' => false,
        ]);
    }

}
