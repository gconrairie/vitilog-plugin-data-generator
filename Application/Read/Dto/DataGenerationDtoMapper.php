<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Read\Dto;

use App\Modules\Cave\Domain\Cave;

class DataGenerationDtoMapper
{
    public function fromArray(array $data, Cave $cave): DataGenerationDto
    {
        return new DataGenerationDto(
            cave: $cave,
            idExploitant: (int) $data['exploitant'] ?? null,
            email: $data['email'] ?? null,
            isPrestataire: $data['isPrestataire'] ?? false,
            idPrestataire: (int) $data['prestataire'] ?? null,
            emailPrestataire: $data['emailPrestataire'] ?? null,
            nParcelles: (int) ($data['parcelles'] ?? 0),
            cepages: $data['cepages'] ?? null,
            production: $data['production'] ?? false,
            productions: $data['productions'] ?? [],
            prelevement: $data['prelevement'] ?? false,
            convocation: $data['convocation'] ?? false,
            acceptConvocation: $data['accept'] ?? false,
            dateConvocation: $data['date'] ?? null,
            inscription: $data['inscription'] ?? false,
            sendInscriptionRequest: $data['inscriptionRequest'] ?? false,
            acceptInscription: $data['acceptInscription'] ?? false,
            events: $data['events'] ?? false,
            passed: $data['passed'] ?? false,
        );
    }
}
