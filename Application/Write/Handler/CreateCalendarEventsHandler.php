<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Cepage\Infrastructure\Repository\CepageRepository;
use App\Modules\Event\Application\Event\Write\Dto\EventCreateDto;
use App\Modules\Event\Application\Event\Write\Handler\CreateEventHandler;
use App\Modules\Parcelle\Infrastructure\Repository\ParcelleRepository;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;

final class CreateCalendarEventsHandler extends AbstractNullableDtoHandler
{
    public function __construct(
        private readonly CreateEventHandler $createEventHandler,
        private readonly ParcelleRepository $parcelleRepository,
        private readonly CepageRepository $cepageRepository,
    ) {
    }

    public function handle(DataGenerationDto $dto): ?array
    {
        if (!$dto->events) {
            return null;
        }

        $cepages = $this->findCepagesForEvents();
        if ([] === $cepages) {
            $cepages = $this->findRandomCepages();
        }

        $day = new \DateTimeImmutable('tomorrow 00:00:00');
        if ($dto->passed) {
            $day = $day->modify('-1 year');
        }

        foreach ($cepages as $index => $cepage) {
            $date = $day->modify('+'.$index.' day');
            $this->createEventHandler->handle(
                cave: $dto->cave,
                dto: new EventCreateDto(
                    date: $date,
                    title: 'Événement généré par le plugin DataGenerator',
                    cepageIds: [(int) $cepage->getId()],
                    published: true,
                    rose: false,
                    comment: 'Événement généré par le plugin DataGenerator',
                    info: null,
                ),
            );
        }

        return [
            'events' => sprintf('Création de %d évènement(s)', count($cepages)),
        ];
    }

    private function findCepagesForEvents(): array
    {
        $parcelles = $this->parcelleRepository->findAll();
        $cepages = [];
        foreach ($parcelles as $parcelle) {
            $cepages[] = $parcelle->getCepage();
        }

        return $cepages;
    }

    private function findRandomCepages(?int $count = 5): array
    {
        $cepages = $this->cepageRepository->findAll();
        $randomCepages = [];
        while (count($randomCepages) < $count) {
            $randomCepage = $cepages[array_rand($cepages)];
            if (!in_array($randomCepage, $randomCepages)) {
                $randomCepages[] = $randomCepage;
            }
        }

        return $randomCepages;
    }
}
