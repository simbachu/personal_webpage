<?php

declare(strict_types=1);

namespace App\Benefactor;

//! @brief Default HTTP client for Patreon OAuth and API calls
final class PatreonHttp
{
    //! @brief Create a stream-wrapper HTTP client
    //! @return callable(string, string, array<string, string>, ?string): string
    public static function createClient(): callable
    {
        return static function (string $method, string $url, array $headers = [], ?string $body = null): string {
            $headerLines = ['User-Agent: simbachu-benefactor'];
            foreach ($headers as $name => $value) {
                $headerLines[] = $name . ': ' . $value;
            }

            $http = [
                'method' => $method,
                'header' => $headerLines,
                'timeout' => 15,
                'ignore_errors' => true,
            ];
            if ($body !== null && $body !== '') {
                $http['content'] = $body;
            }

            $context = stream_context_create(['http' => $http]);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                throw new \RuntimeException('Failed to contact Patreon: ' . $url);
            }
            return $result;
        };
    }
}
