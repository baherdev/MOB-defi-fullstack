<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

// ============================================
// ErrorResponse DTO
// ============================================
class ErrorResponse
{
/**
* @param string[] $details
*/
public function __construct(
public readonly string $message,
public readonly ?string $code = null,
public readonly array $details = [],
) {
}

public function toArray(): array
{
$data = ['message' => $this->message];

if ($this->code !== null) {
$data['code'] = $this->code;
}

if (!empty($this->details)) {
$data['details'] = $this->details;
}

return $data;
}
}
