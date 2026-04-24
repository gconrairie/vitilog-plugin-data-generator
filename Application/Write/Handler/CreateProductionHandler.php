<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Production\Application\Prelevement\Write\Dto\PrelevementWriteDto;
use App\Modules\Production\Application\Prelevement\Write\Handler\CreatePrelevementHandler;
use App\Modules\Production\Application\Production\Write\Dto\ProductionCreateDto;
use App\Modules\Production\Application\Production\Write\Handler\CreateProductionHandler as ModuleCreateProductionHandler;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;

final class CreateProductionHandler extends AbstractNullableDtoHandler
{
    public function __construct(
        private readonly ModuleCreateProductionHandler $createProductionHandler,
        private readonly CreatePrelevementHandler $createPrelevementHandler,
    ) {
    }

    public function handle(DataGenerationDto $dto): ?array
    {
        if (!$dto->production) {
            return null;
        }

        if ([] === $dto->parcelles) {
            return null;
        }

        $dto->productions = [];

        $year = (int) date('Y');
        $annee = $dto->passed ? $year - 1 : $year;

        foreach ($dto->parcelles as $parcelle) {
            $superficie = (int) $parcelle->getSuperficie();
            $qteEstimeeTonnes = max(0.1, ($superficie / 1000) / 0.002 / 1000); // approx. legacy logic

            $dto->productions[] = $this->createProductionHandler->handle(
                new ProductionCreateDto(
                    parcelle: $parcelle,
                    quantiteEstimeeTonnes: (float) $qteEstimeeTonnes,
                    annee: $annee,
                ),
            );

            if ($dto->prelevement) {
                $production = $dto->productions[count($dto->productions) - 1];

                $start = new \DateTimeImmutable('-30 days');
                if ($dto->passed) {
                    $start = $start->modify('-1 year');
                }

                for ($i = 0; $i < 3; ++$i) {
                    $date = $start->modify('+'.($i * 4).' days');
                    $degre = round(6 + (mt_rand() / mt_getrandmax()) * (15 - 6), 1);

                    $this->createPrelevementHandler->handle(
                        new PrelevementWriteDto(
                            production: $production,
                            datePrelevement: $date,
                            degre: $degre,
                        ),
                    );
                }
            }
        }

        return [
            'productions' => sprintf('Création de %d production(s)', count($dto->productions)),
        ];
    }
}
