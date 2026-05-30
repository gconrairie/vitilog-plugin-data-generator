<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Cepage\Infrastructure\Repository\CepageRepository;
use App\Modules\Parcelle\Application\Write\Dto\ParcelleCreateDto;
use App\Modules\Parcelle\Application\Write\Handler\CreateParcelleHandler;
use App\Modules\Parcelle\Infrastructure\Repository\ParcelleRepository;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;
use Faker\Factory as Faker;

final class CreateParcellesHandler extends AbstractNullableDtoHandler
{
    private const M2_TO_HA = 10000;

    public function __construct(
        private readonly CepageRepository $cepageRepository,
        private readonly CreateParcelleHandler $createParcelleHandler,
        private readonly ParcelleRepository $parcelleRepository,
    ) {
    }

    public function handle(DataGenerationDto $dto): ?array
    {
        $p = $this->getFirstNumero();

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

        $min_superficie = 0.75 * self::M2_TO_HA; // 2.5 hectares
        $max_superficie = 8 * self::M2_TO_HA;

        for ($i = 0; $i < $dto->nParcelles; ++$i) {
            $cepage = $selectedCepage ?? $cepagesPool[array_rand($cepagesPool)];
            $numero = strtoupper(substr($cepage->getNom(), 0, 2)).' '.str_pad((string) ($p++), 3, '0', STR_PAD_LEFT);

            $parcelle = $this->createParcelleHandler->handle(
                cave: $dto->cave,
                dto: new ParcelleCreateDto(
                    cepage: $cepage,
                    superficie: $faker->numberBetween($min_superficie, $max_superficie),
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

    private function getFirstNumero(): int
    {
        return count($this->parcelleRepository->findAll());
    }
}
