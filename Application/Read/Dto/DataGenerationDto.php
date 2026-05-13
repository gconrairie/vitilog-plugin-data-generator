<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Read\Dto;

use App\Modules\Cave\Domain\Cave;
use App\Modules\Event\Domain\Event;
use App\Modules\Inscription\Domain\Inscription;
use App\Modules\Production\Domain\Production;
use App\Modules\User\Domain\User;

class DataGenerationDto
{
    public function __construct(
        public Cave $cave,
        // Exploitant
        public ?int $idExploitant = null,
        public ?string $email = null,
        public bool $isPrestataire = false,
        // Prestataire
        public ?int $idPrestataire = null,
        public ?string $emailPrestataire = null,
        // Production
        public int $nParcelles = 1,
        public ?int $cepages = null,
        public bool $production = false,
        public array $productions = [],
        public bool $prelevement = false,
        // Convocation
        public bool $convocation = false,
        public bool $acceptConvocation = false,
        public ?\DateTime $dateConvocation = null,
        // Inscription
        public bool $inscription = false,
        public bool $sendInscriptionRequest = false,
        public bool $acceptInscription = false,
        // Options
        public bool $events = false,
        public bool $passed = false,

        public ?User $exploitant = null,
        public ?User $prestataire = null,
        public ?array $parcelles = [],
        public ?Event $inscriptionEvent = null,
        /**
         * @var array<Inscription>
         */
        public array $inscriptions = [],
    ) {
    }
}
