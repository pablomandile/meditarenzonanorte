<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Un error de la API de Mailchimp, ya escrito para que se entienda en el panel.
 *
 * Mailchimp contesta los errores con el formato RFC 7807:
 *
 *   {"type": "...", "title": "Invalid Resource", "status": 400,
 *    "detail": "Your merge fields were invalid.", "instance": "...",
 *    "errors": [{"field": "html", "message": "..."}]}
 *
 * El `detail` suele alcanzar, pero cuando viene `errors` ahí está el motivo concreto
 * —el que dice que al HTML le falta el link de baja, por ejemplo—, así que se pega
 * al mensaje. El texto termina guardado en la columna `error` del newsletter y se
 * muestra tal cual, así que tiene que poder leerlo una persona, no un programador.
 *
 * Doc: https://mailchimp.com/developer/marketing/docs/errors/
 */
class MailchimpException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 0)
    {
        parent::__construct($message, $status);
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $status = $response->status();

        if (! is_array($body)) {
            // 500 con una página de error, o el proxy del hosting metiendo la cola:
            // no hay JSON que leer, así que se informa lo único que se sabe.
            return new self("Mailchimp respondió {$status} sin detalle.", $status);
        }

        $parts = array_filter([
            $body['detail'] ?? null,
            ...array_map(
                fn ($error) => trim(($error['field'] ?? '').': '.($error['message'] ?? ''), ': '),
                is_array($body['errors'] ?? null) ? $body['errors'] : [],
            ),
        ]);

        $message = $parts === []
            ? ($body['title'] ?? "Mailchimp respondió {$status}.")
            : implode(' ', $parts);

        return new self($message, $status);
    }

    /** El código HTTP: 401 es la key, 403 suele ser "tu plan no incluye esto". */
    public function status(): int
    {
        return $this->status;
    }
}
