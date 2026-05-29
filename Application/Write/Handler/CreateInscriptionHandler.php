<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Event\Application\Event\Write\Dto\EventCreateDto;
use App\Modules\Event\Application\Event\Write\Handler\CreateEventHandler;
use App\Modules\Inscription\Application\Write\Dto\ConfirmInscriptionDto;
use App\Modules\Inscription\Application\Write\Dto\InscriptionWriteDto;
use App\Modules\Inscription\Application\Write\Handler\ConfirmInscriptionHandler;
use App\Modules\Inscription\Application\Write\Handler\CreateInscriptionHandler as ModuleCreateInscriptionHandler;
use App\Modules\Production\Domain\Production;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;

final class CreateInscriptionHandler
{
    public function __construct(
        private readonly CreateEventHandler $createEventHandler,
        private readonly ModuleCreateInscriptionHandler $createInscriptionHandler,
        private readonly ConfirmInscriptionHandler $confirmInscriptionHandler,
    ) {
    }

    public function handle(DataGenerationDto $dto): ?array
    {
        if (!$dto->inscription) {
            return null;
        }

        if (null === $dto->exploitant || [] === $dto->productions) {
            return null;
        }

        $production = $this->pickProduction($dto->productions);
        $cepage = $production->getParcelle()?->getCepage();
        if (null === $cepage || null === $cepage->getId()) {
            throw new \LogicException('Impossible de générer une inscription sans cépage.');
        }

        $date = $this->resolveDate($dto);
        $heureDebut = $date->setTime(10, 0, 0);
        $heureFin = $date->setTime(12, 0, 0);

        $dto->inscriptionEvent = $this->createEventHandler->handle(
            cave: $dto->cave,
            dto: new EventCreateDto(
                date: $date->setTime(0, 0, 0),
                published: true,
                rose: false,
                openAt: $heureDebut,
                closeAt: $heureFin,
                inscription: true,
                cepageId: (int) $cepage->getId(),
                comment: 'Événement d\'inscription généré par le plugin DataGenerator',
                info: null,
            ),
        );

        $response = [
            'inscriptionEvent' => 'Création d\'un évènement ouvert à l\'inscription',
        ];

        if (!$dto->sendInscriptionRequest) {
            return $response;
        }

        $inscription = $this->createInscriptionHandler->handle(
            new InscriptionWriteDto(
                cave: $dto->cave,
                createdBy: $dto->exploitant,
                event: $dto->inscriptionEvent,
                production: $production,
                heureDebut: $heureDebut,
                heureFin: $heureFin,
                quantiteEstimeeTonnes: (float) (($production->getQuantiteEstimeeKg() ?? 0) / 1000),
                comment: 'Demande d\'inscription générée par le plugin DataGenerator',
            ),
        );
        $dto->inscriptions[] = $inscription;
        $response['inscriptions'] = 'Création d\'une demande d\'inscription';

        if (!$dto->acceptInscription) {
            return $response;
        }

        $this->confirmInscriptionHandler->handle(
            $inscription,
            new ConfirmInscriptionDto(
                lieuLivraison: 'Quai '.random_int(1, 10),
                heureDebut: $heureDebut,
                heureFin: $heureFin,
                quantiteDemandeeTonnes: (float) (($production->getQuantiteEstimeeKg() ?? 0) / 1000),
                comment: 'Inscription acceptée par le plugin DataGenerator',
            ),
        );
        $response['acceptedInscriptions'] = 'Acceptation de la demande et création de la convocation associée';

        return $response;
    }

    /**
     * @param array<Production> $productions
     */
    private function pickProduction(array $productions): Production
    {
        $production = $productions[array_rand($productions)];
        if (!$production instanceof Production) {
            throw new \LogicException('Production invalide pour générer une inscription.');
        }

        return $production;
    }

    private function resolveDate(DataGenerationDto $dto): \DateTimeImmutable
    {
        $date = null !== $dto->dateConvocation ? clone $dto->dateConvocation : new \DateTime('+1 day');
        if ($dto->passed) {
            $date->modify('-1 year');
        }

        return \DateTimeImmutable::createFromMutable($date);
    }
}
