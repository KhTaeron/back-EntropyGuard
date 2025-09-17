<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $email = (string) ($data['email'] ?? '');
        $pseudo = (string) ($data['pseudo'] ?? '');
        $plain = (string) ($data['password'] ?? '');

        $violations = [];
        $v = $validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($v))
            $violations[] = 'Invalid email';
        $v = $validator->validate($pseudo, [new Assert\NotBlank(), new Assert\Length(min: 3, max: 32)]);
        if (count($v))
            $violations[] = 'Invalid pseudo';
        $v = $validator->validate($plain, [new Assert\NotBlank(), new Assert\Length(min: 8)]);
        if (count($v))
            $violations[] = 'Password too short (min 8)';

        if ($violations) {
            return $this->json(['error' => implode(', ', $violations)], 400);
        }

        $repo = $em->getRepository(User::class);
        if ($repo->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email already used'], 409);
        }

        $user = (new User())
            ->setEmail($email)
            ->setPseudo($pseudo)
            ->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, $plain));

        $em->persist($user);
        $em->flush();

        return $this->json([
            'ok' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
        ], 201);
    }
}
