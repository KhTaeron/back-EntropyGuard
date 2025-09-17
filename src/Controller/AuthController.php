<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PasswordStrengthService;
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
        ValidatorInterface $validator,
        PasswordStrengthService $pwdStrength
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

        $passwordViolations = $validator->validate($plain, [
            new Assert\NotBlank(message: 'Password is required.'),
            new Assert\Length(min: 12, minMessage: 'Password must be at least 12 characters long.'),
            new Assert\Regex(pattern: '/[a-z]/', message: 'Password must contain at least one lowercase letter.'),
            new Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter.'),
            new Assert\Regex(pattern: '/\d/', message: 'Password must contain at least one digit.'),
            new Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Password must contain at least one special character.'),
        ]);
        if (count($passwordViolations)) {
            foreach ($passwordViolations as $v) {
                $violations[] = $v->getMessage();
            }
        }

        if ($violations) {
            return $this->json(['error' => implode(' ', $violations)], 400);
        }

        [$score, $tips] = $pwdStrength->score($plain, [$email, $pseudo]);
        if ($score < PasswordStrengthService::MIN_SCORE) {
            return $this->json([
                'error' => 'Password too weak.',
                'score' => $score,
                'min_score' => PasswordStrengthService::MIN_SCORE,
                'suggestions' => $tips,
            ], 422);
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
