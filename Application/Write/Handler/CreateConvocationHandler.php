<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Convocation\Application\Write\Dto\ConvocationCreateDto;
use App\Modules\Convocation\Application\Write\Handler\CreateConvocationHandler as ModuleCreateConvocationHandler;
use App\Modules\Convocation\Domain\Enum\ConvocationResponse;
use App\Modules\User\Domain\User;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CreateConvocationHandler extends AbstractNullableDtoHandler
{
    public function __construct(
        private readonly ModuleCreateConvocationHandler $createConvocationHandler,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handle(DataGenerationDto $dto): ?array
    {
        if (!$dto->convocation) {
            return null;
        }

        if (empty($dto->productions)) {
            return null;
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new \LogicException('Aucun utilisateur authentifié.');
        }

        $date = $dto->dateConvocation ?? new \DateTime('+1 day');
        if ($dto->passed) {
            $date->modify('-1 year');
        }

        $dateImmutable = \DateTimeImmutable::createFromMutable($date)->setTime(0, 0);
        $heureDebut = $dateImmutable->setTime(10, 0, 0);
        $heureFin = $dateImmutable->setTime(12, 0, 0);
        $responseStatus = $dto->acceptConvocation ? ConvocationResponse::ACCEPTED : ConvocationResponse::NONE;

        foreach ($dto->productions as $production) {
            $convocation = $this->createConvocationHandler->handle(
                $currentUser,
                $dto->cave,
                new ConvocationCreateDto(
                    createdBy: $currentUser,
                    cave: $dto->cave,
                    production: $production,
                    dateConvocation: $dateImmutable,
                    heureDebut: $heureDebut,
                    heureFin: $heureFin,
                    quantiteDemandeeTonnes: (float) (($production->getQuantiteEstimeeKg() ?? 0) / 1000),
                    quai: (string) random_int(1, 10),
                ),
            );

            $convocation->setResponseStatus($responseStatus);
            $this->entityManager->persist($convocation);
        }
        $this->entityManager->flush();

        return [
            'convocations' => sprintf('Création de %d convocation(s)', count($dto->productions)),
        ];
    }
}
