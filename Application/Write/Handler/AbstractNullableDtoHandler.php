<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;

abstract class AbstractNullableDtoHandler
{
    abstract public function handle(DataGenerationDto $dto): ?array;

    final protected function shouldSkip(mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        if (is_array($value) && [] === $value) {
            return true;
        }

        if (is_string($value) && '' === trim($value)) {
            return true;
        }

        return false;
    }
}
