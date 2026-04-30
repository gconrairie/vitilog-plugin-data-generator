<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Read\Handler;

use App\Modules\Apport\Domain\Apport;
use App\Modules\Cave\Domain\Cave;
use App\Modules\Convocation\Domain\Convocation;
use App\Modules\Event\Domain\Event;
use App\Modules\Notification\Domain\Message\EmailMessage;
use App\Modules\Notification\Domain\Message\SmsMessage;
use App\Modules\Notification\Domain\Notification;
use App\Modules\Parcelle\Domain\Parcelle;
use App\Modules\Production\Domain\Production;
use App\Modules\Production\Infrastructure\Repository\PrelevementRepository;
use App\Modules\User\Domain\User;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReadDatabaseHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly PrelevementRepository $prelevementRepository,
    ) {}

    public function handle(Cave $cave): array
    {
        $totalUsers = $this->userRepository->findAllForCave($cave);
        $totalExploitant = count(array_filter($totalUsers, fn(User $user) => in_array('ROLE_EXPLOITANT', $user->getRoles(), true)));
        $totalPrestataire = count(array_filter($totalUsers, fn(User $user) => in_array('ROLE_PRESTATAIRE', $user->getRoles(), true)));

        $totalParcels = $this->entityManager->getRepository(Parcelle::class)->count(['cave' => $cave]);
        $totalProductions = $this->entityManager->getRepository(Production::class)->count(['cave' => $cave]);
        $totalPrelevements = $this->prelevementRepository->findAllForCave($cave);

        $totalEvents = $this->entityManager->getRepository(Event::class)->count(['cave' => $cave]);
        $totalConvocations = $this->entityManager->getRepository(Convocation::class)->count(['cave' => $cave]);
        $totalApports = $this->entityManager->getRepository(Apport::class)->count(['cave' => $cave]);

        $totalNotifications = $this->entityManager->getRepository(Notification::class)->count(['cave' => $cave]);
        $totalEmailMessages = $this->entityManager->getRepository(EmailMessage::class)->count(['cave' => $cave]);
        $totalSmsMessages = $this->entityManager->getRepository(SmsMessage::class)->count(['cave' => $cave]);

        return [
            'users' => count($totalUsers),

            'exploitants' => $totalExploitant,
            'prestataires' => $totalPrestataire,

            'parcelles' => $totalParcels,
            'productions' => $totalProductions,
            'prelevements' => count($totalPrelevements),

            'events' => $totalEvents,
            'convocations' => $totalConvocations,
            'apports' => $totalApports,

            'notifications' => $totalNotifications,
            'emailMessages' => $totalEmailMessages,
            'smsMessages' => $totalSmsMessages,
        ];
    }
}
