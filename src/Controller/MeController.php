<?php
// src/Controller/MeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MeController extends AbstractController
{
    #[Route('/api/me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $u = $this->getUser();
        return $this->json([
            'id'     => method_exists($u,'getId') ? $u->getId() : null,
            'email'  => method_exists($u,'getEmail') ? $u->getEmail() : null,
            'pseudo' => method_exists($u,'getPseudo') ? $u->getPseudo() : null,
            'roles'  => method_exists($u,'getRoles') ? $u->getRoles() : [],
        ]);
    }
}
