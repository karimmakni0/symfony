<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LanguageController extends AbstractController
{
    #[Route('/change-language/{language}', name: 'app_change_language')]
    public function changeLanguage(Request $request, string $language): Response
    {
        // Validate language is one of the supported ones
        $supportedLanguages = ['en', 'fr', 'ar', 'de'];
        if (!in_array($language, $supportedLanguages)) {
            $language = 'en'; // Default to English if invalid language
        }
        
        // Store the selected language in the session
        $request->getSession()->set('_locale', $language);
        
        // Redirect back to the previous page or homepage
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_home');
    }
}
