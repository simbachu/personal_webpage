<?php

declare(strict_types=1);

namespace App\Router;

use App\Type\TemplateName;

//! @brief Value object representing the result of route handling
//!
//! This class encapsulates the result of processing a route, including
//! the template to render and the data to pass to the template.
//!
//! @code
//! // Example usage:
//! $result = new RouteResult(
//!     TemplateName::HOME,
//!     ['title' => 'Home Page', 'content' => 'Welcome!']
//! );
//!
//! echo $result->getTemplate()->value; // "home"
//! $data = $result->getData(); // ['title' => 'Home Page', ...]
//! @endcode
class RouteResult
{
    private TemplateName $template; //!< Template to render
    private array $data; //!< Data to pass to template
    private int $statusCode; //!< HTTP status code

    //! @brief Construct a new RouteResult instance
    //! @param template The template to render
    //! @param data The data to pass to the template
    //! @param statusCode The HTTP status code (defaults to 200)
    public function __construct(TemplateName $template, array $data = [], int $statusCode = 200)
    {
        $this->template = $template;
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    //! @brief Get the template to render
    //! @return TemplateName The template enum value
    public function getTemplate(): TemplateName
    {
        return $this->template;
    }

    //! @brief Get the data to pass to the template
    //! @return array The data array
    public function getData(): array
    {
        return $this->data;
    }

    //! @brief Get the HTTP status code
    //! @return int The status code
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    //! @brief Create a new result with merged data
    //! @param data Additional data to merge
    //! @return RouteResult New result with merged data
    public function withData(array $data): self
    {
        return new self(
            $this->template,
            array_merge($this->data, $data),
            $this->statusCode
        );
    }

    //! @brief Create a new result with updated status code
    //! @param statusCode The new status code
    //! @return RouteResult New result with updated status code
    public function withStatusCode(int $statusCode): self
    {
        return new self($this->template, $this->data, $statusCode);
    }
}
