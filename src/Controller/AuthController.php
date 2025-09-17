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

        // Email
        $emailViolations = $validator->validate($email, [
            new Assert\NotBlank(message: 'Email is required.'),
            new Assert\Email(message: 'Invalid email.'),
        ]);
        if (count($emailViolations)) {
            foreach ($emailViolations as $v) {
                $violations[] = $v->getMessage();
            }
        }

        // Pseudo
        $pseudoViolations = $validator->validate($pseudo, [
            new Assert\NotBlank(message: 'Pseudo is required.'),
            new Assert\Length(min: 3, max: 32, minMessage: 'Pseudo must be at least 3 characters.', maxMessage: 'Pseudo must be at most 32 characters.'),
        ]);
        if (count($pseudoViolations)) {
            foreach ($pseudoViolations as $v) {
                $violations[] = $v->getMessage();
            }
        }

        // Password rules:
        // - min 12 chars
        // - at least one lowercase, one uppercase, one digit, one special char
        // NOTE: le "caractère spécial" est ici défini comme tout caractère non alphanumérique ASCII.
        // Si tu veux limiter à un set précis, remplace le dernier Regex par un set whitelist.
        $passwordViolations = $validator->validate($plain, [
            new Assert\NotBlank(message: 'Password is required.'),
            new Assert\Length(min: 12, minMessage: 'Password must be at least 12 characters long.'),
            new Assert\Regex(pattern: '/[a-z]/', message: 'Password must contain at least one lowercase letter.'),
            new Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter.'),
            new Assert\Regex(pattern: '/\d/',   message: 'Password must contain at least one digit.'),
            // Variante stricte ASCII punctuation (exclut les lettres accentuées) :
            // new Assert\Regex(pattern: '/[!@#$%^&*()_\-+=[\]{};:\'",.<>\/?\\|`~]/', message: 'Password must contain at least one special character.')
            new Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Password must contain at least one special character.'),
        ]);
        if (count($passwordViolations)) {
            foreach ($passwordViolations as $v) {
                $violations[] = $v->getMessage();
            }
        }

        if ($violations) {
            // Tu peux passer à 422 si tu préfères "Unprocessable Entity"
            return $this->json(['error' => implode(' ', $violations)], 400);
        }

        // Unicité email (tu peux faire pareil pour pseudo si nécessaire)
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
