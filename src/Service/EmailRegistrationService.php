<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use InvalidArgumentException;
use RuntimeException;

//! @brief Service for email registration with secret code verification
//!
//! Handles email registration flow: generates secret codes, sends verification emails,
//! and verifies codes to register users.
final class EmailRegistrationService
{
    private const SECRET_LENGTH = 8;
    private const SECRET_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excludes ambiguous chars

    //! @brief Construct email registration service
    //! @param mailer Symfony Mailer instance
    //! @param fromEmail Email address to send from
    //! @param baseUrl Base URL for verification links (optional)
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
        private readonly ?string $baseUrl = null
    ) {
        if (empty($fromEmail)) {
            throw new InvalidArgumentException('From email cannot be empty');
        }

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid from email: $fromEmail");
        }
    }

    //! @brief Generate a random secret code
    //! @return string 8-character secret code
    public function generateSecret(): string
    {
        $secret = '';
        $charsLength = strlen(self::SECRET_CHARS);
        
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= self::SECRET_CHARS[random_int(0, $charsLength - 1)];
        }
        
        return $secret;
    }

    //! @brief Send registration email with secret code
    //! @param email User's email address
    //! @param secret Secret code to include
    //! @throws \InvalidArgumentException If email is invalid
    //! @throws \RuntimeException If email sending fails
    public function sendRegistrationEmail(string $email, string $secret): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: $email");
        }

        if (strlen($secret) !== self::SECRET_LENGTH) {
            throw new InvalidArgumentException("Secret must be " . self::SECRET_LENGTH . " characters");
        }

        try {
            $emailMessage = (new Email())
                ->from($this->fromEmail)
                ->to($email)
                ->subject('Pokémon Tournament Registration Code')
                ->html($this->buildEmailHtml($email, $secret))
                ->text($this->buildEmailText($email, $secret));

            $this->mailer->send($emailMessage);
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException(
                "Failed to send registration email to $email: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    //! @brief Build HTML email body
    //! @param email User's email
    //! @param secret Secret code
    //! @return string HTML email content
    private function buildEmailHtml(string $email, string $secret): string
    {
        $codeDisplay = $this->formatSecretForDisplay($secret);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .code { 
            font-size: 32px; 
            font-weight: bold; 
            letter-spacing: 8px; 
            text-align: center; 
            padding: 20px; 
            background: #f0f0f0; 
            border-radius: 8px; 
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }
        .footer { margin-top: 40px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Pokémon Tournament Ranking!</h1>
        
        <p>Thank you for registering with email: <strong>$email</strong></p>
        
        <p>Your registration code is:</p>
        
        <div class="code">$codeDisplay</div>
        
        <p>Please enter this code on the registration page to complete your registration and start ranking Pokémon!</p>
        
        <p>The code is valid for 24 hours.</p>
        
        <div class="footer">
            <p>If you didn't request this code, please ignore this email.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    //! @brief Build plain text email body
    //! @param email User's email
    //! @param secret Secret code
    //! @return string Plain text email content
    private function buildEmailText(string $email, string $secret): string
    {
        $codeDisplay = $this->formatSecretForDisplay($secret);
        
        return <<<TEXT
Welcome to Pokémon Tournament Ranking!

Thank you for registering with email: $email

Your registration code is:

$codeDisplay

Please enter this code on the registration page to complete your registration and start ranking Pokémon!

The code is valid for 24 hours.

If you didn't request this code, please ignore this email.
TEXT;
    }

    //! @brief Format secret code for display (add spaces)
    //! @param secret Secret code
    //! @return string Formatted secret code
    private function formatSecretForDisplay(string $secret): string
    {
        // Split into groups of 4 for readability: XXXX XXXX
        return chunk_split($secret, 4, ' ');
    }
}

