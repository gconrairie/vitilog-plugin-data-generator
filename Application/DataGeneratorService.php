<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application;

use App\Modules\Cave\Domain\Cave;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDtoMapper;
use App\Plugins\DataGenerator\Application\Write\Handler\CreateCalendarEventsHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\CreateConvocationHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\CreateInscriptionHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\CreateParcellesHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\CreateProductionHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\CreateUserHandler;

final class DataGeneratorService
{
    public function __construct(
        private readonly DataGenerationDtoMapper $dataGenerationDtoMapper,
        private readonly CreateUserHandler $createUserHandler,
        private readonly CreateParcellesHandler $createParcellesHandler,
        private readonly CreateProductionHandler $createProductionHandler,
        private readonly CreateConvocationHandler $createConvocationHandler,
        private readonly CreateInscriptionHandler $createInscriptionHandler,
        private readonly CreateCalendarEventsHandler $createCalendarEventsHandler,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handleForm(array $payload, Cave $cave): array
    {
        $dto = $this->dataGenerationDtoMapper->fromArray($payload, $cave);

        $response = [];
        $response['exploitant'] = $this->createUserHandler->handleExploitant($dto);
        $response['prestataire'] = $this->createUserHandler->handlePrestataire($dto);
        $response['parcelles'] = $this->createParcellesHandler->handle($dto, $cave);
        $response['productions'] = $this->createProductionHandler->handle($dto);
        $response['convocations'] = $this->createConvocationHandler->handle($dto);
        $response['inscriptions'] = $this->createInscriptionHandler->handle($dto);
        $response['calendarEvents'] = $this->createCalendarEventsHandler->handle($dto);

        return $response;
    }
}
