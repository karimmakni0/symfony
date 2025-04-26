<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{
    /**
     * Serve files from the uploads directory
     * 
     * @Route("/uploads/{filePath}", name="serve_uploaded_file", requirements={"filePath"=".+"})
     */
    public function serveUploadedFile(string $filePath): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $filePath;
        
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('The file does not exist');
        }
        
        return new BinaryFileResponse($filePath);
    }

    /**
     * Serve files from the assets directory
     * 
     * @Route("/assets/{filePath}", name="serve_asset_file", requirements={"filePath"=".+"})
     */
    public function serveAssetFile(string $filePath): Response
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/assets/' . $filePath;
        
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('The asset file does not exist');
        }
        
        return new BinaryFileResponse($filePath);
    }
    
    /**
     * Serve files from the img directory (without assets prefix)
     * 
     * @Route("/img/{filePath}", name="serve_img_file", requirements={"filePath"=".+"})
     */
    public function serveImgFile(string $filePath): Response
    {
        // First try the path with assets prefix
        $filePath1 = $this->getParameter('kernel.project_dir') . '/public/assets/img/' . $filePath;
        $filePath2 = $this->getParameter('kernel.project_dir') . '/public/img/' . $filePath;
        
        if (file_exists($filePath1)) {
            return new BinaryFileResponse($filePath1);
        } else if (file_exists($filePath2)) {
            return new BinaryFileResponse($filePath2);
        } else {
            throw new NotFoundHttpException('The image file does not exist');
        }
    }
}
