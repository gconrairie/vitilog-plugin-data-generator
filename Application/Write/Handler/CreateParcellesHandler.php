<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Cepage\Infrastructure\Repository\CepageRepository;
use App\Modules\Parcelle\Application\Write\Dto\ParcelleCreateDto;
use App\Modules\Parcelle\Application\Write\Handler\CreateParcelleHandler;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;
use Faker\Factory as Faker;

final class CreateParcellesHandler extends AbstractNullableDtoHandler
{
    public function __construct(
        private readonly CepageRepository $cepageRepository,
        private readonly CreateParcelleHandler $createParcelleHandler,
    ) {
    }

    public function handle(DataGenerationDto $dto): ?array
    {
        if (null === $dto->exploitant) {
            return null;
        }

        if ($dto->nParcelles <= 0) {
            return null;
        }

        $response = [];

        $faker = Faker::create('fr_FR');

        $selectedCepage = null;
        if (!$this->shouldSkip($dto->cepages)) {
            $selectedCepage = $this->cepageRepository->find($dto->cepages);
        }

        $cepagesPool = null;
        if (null === $selectedCepage) {
            $cepagesPool = $this->cepageRepository->findAll();
            if ([] === $cepagesPool) {
                throw new \LogicException('Aucun cépage disponible.');
            }
        }

        $dto->parcelles = [];

        for ($i = 0; $i < $dto->nParcelles; ++$i) {
            $cepage = $selectedCepage ?? $cepagesPool[array_rand($cepagesPool)];
            $numero = strtoupper(substr($cepage->getNom(), 0, 2)).' '.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);

            $parcelle = $this->createParcelleHandler->handle(
                cave: $dto->cave,
                dto: new ParcelleCreateDto(
                    cepage: $cepage,
                    superficie: $faker->numberBetween(2500, 55000),
                    anneePlantation: random_int(1982, (int) date('Y')),
                    commune: $faker->city(),
                    lieuDit: $faker->city(),
                    exploitant: $dto->exploitant,
                    prestataire: $dto->prestataire,
                    numero: $numero,
                ),
            );

            $dto->parcelles[] = $parcelle;
        }

        $response['parcelles'] = sprintf('Création de %d parcelle(s)', count($dto->parcelles));

        return $response;
    }
}
