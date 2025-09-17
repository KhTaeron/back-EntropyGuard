<?php

namespace App\Service;

/**
 * Service to estimate the strength of a password and return a score (0–100)
 * along with improvement suggestions.
 */
final class PasswordStrengthService
{
    public const MIN_SCORE = 60; // Minimum score required to accept a password

    /**
     * Evaluate password strength.
     *
     * @param string $pwd The password to evaluate
     * @param array  $userHints user-related strings (e.g. email, username to penalize if present in the password.
     *
     * @return array [int $score, string[] $suggestions]
     */
    public function score(string $pwd, array $userHints = []): array
    {
        $pwd = trim($pwd); // removes whitespaces and invisible characters
        if ($pwd === '') {
            return [0, ['Use a longer password.']];
        }

        $len = mb_strlen($pwd);

        // Character set checks
        $hasLower  = (bool) preg_match('/[a-z]/u', $pwd);
        $hasUpper  = (bool) preg_match('/[A-Z]/u', $pwd);
        $hasDigit  = (bool) preg_match('/\d/u', $pwd);
        $hasSymbol = (bool) preg_match('/[^A-Za-z0-9]/u', $pwd);

        // Estimate alphabet size based on character diversity
        $alphabet = 0;
        if ($hasLower)  $alphabet += 26;
        if ($hasUpper)  $alphabet += 26;
        if ($hasDigit)  $alphabet += 10;
        if ($hasSymbol) $alphabet += 33;

        // Approximate entropy in bits -> how hard it is to brute-force this password
        $entropyBits = ($alphabet > 0) ? $len * (log($alphabet) / log(2)) : 0;

        // Normalize entropy into a score from 0 to 100
        $baseScore = min(100, (int) round($entropyBits / 1.2));

        $penalty = 0;
        $lowerPwd = mb_strtolower($pwd); // lowercase to compare with the following sequences

        // Penalize common sequences (abcd, 1234, qwerty...)
        $sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'qwertyuiopasdfghjklzxcvbnm',
            '0123456789'
        ];
        foreach ($sequences as $seq) {
            for ($i = 0; $i < mb_strlen($seq) - 3; $i++) {
                $chunk = mb_substr($seq, $i, 4);
                if (str_contains($lowerPwd, $chunk) || str_contains($lowerPwd, strrev($chunk))) {
                    $penalty += 12;
                }
            }
        }

        // Penalize repeated characters (e.g. "aaa", "111")
        if (preg_match('/(.)\1{2,}/u', $pwd)) {
            $penalty += 10;
        }

        // Penalize if the password contains parts of the user's info (email, username...)
        foreach ($userHints as $hint) {
            $h = mb_strtolower((string) $hint);
            if ($h !== '' && mb_strlen($h) >= 3 && str_contains($lowerPwd, $h)) {
                $penalty += 15;
            }
        }

        // Penalize very common passwords
        $common = ['password','azerty','qwerty','welcome','admin','letmein','motdepasse'];
        foreach ($common as $bad) {
            if (str_contains($lowerPwd, $bad)) {
                $penalty += 25;
            }
        }

        // Final score between 0 and 100
        $score = max(0, min(100, $baseScore - $penalty));

        // Suggestions for improvement
        $tips = [];
        if ($len < 12)                 $tips[] = 'Use at least 12 characters.';
        if (!$hasLower || !$hasUpper)  $tips[] = 'Mix UPPER and lower case.';
        if (!$hasDigit)                $tips[] = 'Add some digits.';
        if (!$hasSymbol)               $tips[] = 'Add special characters.';
        if ($penalty >= 10)            $tips[] = 'Avoid common patterns, sequences, or personal info.';

        return [$score, $tips];
    }
}
