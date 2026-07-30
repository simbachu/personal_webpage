<?php

declare(strict_types=1);

namespace App\Cv;

use App\Shared\Support\Result;

//! @brief A single certificate entry
final class CertificateEntry
{
    //! @param name Certificate name
    //! @param issuer Issuing organization
    //! @param issued Issue date (YYYY-MM)
    //! @param grade Optional grade
    //! @param credentialId Optional credential identifier
    public function __construct(
        public readonly string $name,
        public readonly string $issuer,
        public readonly string $issued,
        public readonly ?string $grade = null,
        public readonly ?string $credentialId = null,
    ) {}

    //! @brief Parse an untyped certificate object
    //! @param data Untyped associative array
    //! @return Result<self>
    public static function parse(array $data): Result
    {
        $context = 'Certificate entry';

        $name = CvParsing::requireString($data, 'name', $context);
        if ($name->isFailure()) {
            return $name;
        }

        $issuer = CvParsing::requireString($data, 'issuer', $context);
        if ($issuer->isFailure()) {
            return $issuer;
        }

        $issued = CvParsing::requireString($data, 'issued', $context);
        if ($issued->isFailure()) {
            return $issued;
        }

        $grade = CvParsing::optionalString($data, 'grade', $context);
        if ($grade->isFailure()) {
            return $grade;
        }

        $credentialId = CvParsing::optionalString($data, 'credential_id', $context);
        if ($credentialId->isFailure()) {
            return $credentialId;
        }

        return Result::success(new self(
            name: $name->getValue(),
            issuer: $issuer->getValue(),
            issued: $issued->getValue(),
            grade: $grade->getValue(),
            credentialId: $credentialId->getValue(),
        ));
    }

    //! @brief Parse a list of certificate entries
    //! @param items Untyped list
    //! @return Result<list<self>>
    public static function parseList(array $items): Result
    {
        if (!array_is_list($items)) {
            return Result::failure('Certificates must be a list');
        }

        $entries = [];
        foreach ($items as $index => $item) {
            if (!is_array($item) || array_is_list($item)) {
                return Result::failure("Certificates[{$index}] must be an object");
            }

            $parsed = self::parse($item);
            if ($parsed->isFailure()) {
                return Result::failure("Certificates[{$index}]: " . $parsed->getError());
            }

            $entries[] = $parsed->getValue();
        }

        return Result::success($entries);
    }

    //! @brief Template-facing array representation
    //! @return array<string, mixed>
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'issuer' => $this->issuer,
            'issued' => $this->issued,
        ];

        if ($this->grade !== null) {
            $data['grade'] = $this->grade;
        }
        if ($this->credentialId !== null) {
            $data['credential_id'] = $this->credentialId;
        }

        return $data;
    }
}
