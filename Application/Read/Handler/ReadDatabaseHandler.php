<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Read\Handler;

use App\Modules\Apport\Domain\Apport;
use App\Modules\Cave\Domain\Cave;
use App\Modules\Cave\Infrastructure\Context\CaveContext;
use App\Modules\Convocation\Domain\Convocation;
use App\Modules\Event\Domain\Event;
use App\Modules\Notification\Domain\Message\EmailMessage;
use App\Modules\Notification\Domain\Message\SmsMessage;
use App\Modules\Notification\Domain\Notification;
use App\Modules\Parcelle\Domain\Parcelle;
use App\Modules\Production\Domain\Prelevement;
use App\Modules\Production\Domain\Production;
use App\Modules\User\Domain\User;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReadDatabaseHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly CaveContext $caveContext,
    ) {
    }

    public function handle(): array
    {
        $cave = $this->caveContext->getCave();

        $totalCaves = $this->entityManager->getRepository(Cave::class)->count([]);
        $totalUsers = $this->userRepository->findAllForCave($cave);
        $totalExploitant = count(array_filter($totalUsers, fn (User $user) => in_array('ROLE_EXPLOITANT', $user->getRoles(), true)));
        $totalPrestataire = count(array_filter($totalUsers, fn (User $user) => in_array('ROLE_PRESTATAIRE', $user->getRoles(), true)));

        $totalParcels = $this->entityManager->getRepository(Parcelle::class)->count([]);
        $totalProductions = $this->entityManager->getRepository(Production::class)->count([]);
        $totalPrelevements = $this->entityManager->getRepository(Prelevement::class)->count([]);

        $totalEvents = $this->entityManager->getRepository(Event::class)->count([]);
        $totalConvocations = $this->entityManager->getRepository(Convocation::class)->count([]);
        $totalApports = $this->entityManager->getRepository(Apport::class)->count([]);

        $totalNotifications = $this->entityManager->getRepository(Notification::class)->count([]);
        $totalEmailMessages = $this->entityManager->getRepository(EmailMessage::class)->count([]);
        $totalSmsMessages = $this->entityManager->getRepository(SmsMessage::class)->count([]);

        return [
            'caves' => $totalCaves,
            'users' => count($totalUsers),

            'exploitants' => $totalExploitant,
            'prestataires' => $totalPrestataire,

            'parcelles' => $totalParcels,
            'productions' => $totalProductions,
            'prelevements' => $totalPrelevements,

            'events' => $totalEvents,
            'convocations' => $totalConvocations,
            'apports' => $totalApports,

            'notifications' => $totalNotifications,
            'emailMessages' => $totalEmailMessages,
            'smsMessages' => $totalSmsMessages,
        ];
    }
}
