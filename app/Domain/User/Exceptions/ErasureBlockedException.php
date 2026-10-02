<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class ErasureBlockedException extends DomainException
{
    /**
     * @param  list<string>  $reasons
     */
    public static function because(array $reasons): self
    {
        return (new self('Your account cannot be deleted yet.'))
            ->withContext(['reasons' => array_values($reasons)]);
    }
}
