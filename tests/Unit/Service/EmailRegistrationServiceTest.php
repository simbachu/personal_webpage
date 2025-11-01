<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use App\Service\EmailRegistrationService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use InvalidArgumentException;
use RuntimeException;

//! @brief Unit tests for EmailRegistrationService
//!
//! Tests secret generation, email validation, email sending, and formatting.
final class EmailRegistrationServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private string $fromEmail;

    //! @brief Set up test fixtures
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->fromEmail = 'noreply@example.com';
    }

    //! @brief Test secret code generation produces correct format
    public function test_generate_secret_produces_8_character_code(): void
    {
        //! @section Arrange
        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act
        $secret = $service->generateSecret();

        //! @section Assert
        $this->assertSame(8, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-9]+$/', $secret); // Only valid characters
        $this->assertStringNotContainsString('0', $secret); // No zero
        $this->assertStringNotContainsString('1', $secret); // No one
        $this->assertStringNotContainsString('I', $secret); // No capital I
        $this->assertStringNotContainsString('O', $secret); // No capital O
    }

    //! @brief Test secret codes are unique
    public function test_generate_secret_produces_unique_codes(): void
    {
        //! @section Arrange
        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);
        $secrets = [];

        //! @section Act
        for ($i = 0; $i < 100; $i++) {
            $secrets[] = $service->generateSecret();
        }

        //! @section Assert
        $uniqueSecrets = array_unique($secrets);
        $this->assertCount(100, $uniqueSecrets, 'All secrets should be unique');
    }

    //! @brief Test email validation rejects invalid emails
    public function test_send_registration_email_rejects_invalid_email(): void
    {
        //! @section Arrange
        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address');
        $service->sendRegistrationEmail('invalid-email', 'ABCD1234');
    }

    //! @brief Test email validation rejects too short secret
    public function test_send_registration_email_rejects_too_short_secret(): void
    {
        //! @section Arrange
        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret must be 8 characters');
        $service->sendRegistrationEmail('user@example.com', 'SHORT'); // 5 characters
    }

    //! @brief Test email validation rejects too long secret
    public function test_send_registration_email_rejects_too_long_secret(): void
    {
        //! @section Arrange
        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret must be 8 characters');
        $service->sendRegistrationEmail('user@example.com', 'TOOLONG123'); // 10 characters
    }

    //! @brief Test email sending succeeds with valid input
    public function test_send_registration_email_succeeds(): void
    {
        //! @section Arrange
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getFrom()[0]->getAddress() === $this->fromEmail
                    && $email->getTo()[0]->getAddress() === 'user@example.com'
                    && $email->getSubject() === 'Pokémon Tournament Registration Code'
                    && str_contains($email->getHtmlBody() ?? '', 'ABCD')
                    && str_contains($email->getTextBody() ?? '', 'ABCD');
            }));

        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act
        $service->sendRegistrationEmail('user@example.com', 'ABCD1234');
    }

    //! @brief Test email includes formatted secret code
    public function test_email_includes_formatted_secret_code(): void
    {
        //! @section Arrange
        $capturedEmail = null;
        $this->mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$capturedEmail) {
                $capturedEmail = $email;
            });

        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act
        $service->sendRegistrationEmail('user@example.com', 'ABCD1234');

        //! @section Assert
        $this->assertNotNull($capturedEmail);
        $htmlBody = $capturedEmail->getHtmlBody();
        $this->assertStringContainsString('ABCD 1234', $htmlBody);
        $textBody = $capturedEmail->getTextBody();
        $this->assertStringContainsString('ABCD 1234', $textBody);
    }

    //! @brief Test email includes user email address
    public function test_email_includes_user_email(): void
    {
        //! @section Arrange
        $capturedEmail = null;
        $this->mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$capturedEmail) {
                $capturedEmail = $email;
            });

        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act
        $service->sendRegistrationEmail('test@example.com', 'ABCD1234');

        //! @section Assert
        $this->assertNotNull($capturedEmail);
        $htmlBody = $capturedEmail->getHtmlBody();
        $this->assertStringContainsString('test@example.com', $htmlBody);
    }

    //! @brief Test email sending failure propagates exception
    public function test_send_registration_email_handles_transport_failure(): void
    {
        //! @section Arrange
        $exception = $this->createMock(TransportExceptionInterface::class);
        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $service = new EmailRegistrationService($this->mailer, $this->fromEmail);

        //! @section Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send registration email');
        $service->sendRegistrationEmail('user@example.com', 'ABCD1234');
    }

    //! @brief Test constructor rejects invalid from email
    public function test_constructor_rejects_invalid_from_email(): void
    {
        //! @section Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid from email');
        new EmailRegistrationService($this->mailer, 'invalid-email');
    }

    //! @brief Test constructor rejects empty from email
    public function test_constructor_rejects_empty_from_email(): void
    {
        //! @section Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('From email cannot be empty');
        new EmailRegistrationService($this->mailer, '');
    }
}

