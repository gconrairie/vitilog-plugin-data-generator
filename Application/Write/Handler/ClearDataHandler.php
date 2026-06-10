<?php

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Cave\Domain\Cave;
use App\Modules\Cave\Infrastructure\Context\CaveContext;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ClearDataHandler
{
    public const TARGET_ALL = 'all';
    public const TARGET_EVENTS = 'events';
    public const TARGET_INSCRIPTIONS = 'inscriptions';
    public const TARGET_CONVOCATIONS = 'convocations';
    public const TARGET_PARCELLES = 'parcelles';
    public const TARGET_PRODUCTIONS = 'productions';
    public const TARGET_PRELEVEMENTS = 'prelevements';

    public const TARGET_LABELS = [
        self::TARGET_ALL => 'Tout',
        self::TARGET_EVENTS => 'Events',
        self::TARGET_INSCRIPTIONS => 'Inscriptions',
        self::TARGET_CONVOCATIONS => 'Convocations',
        self::TARGET_PARCELLES => 'Parcelles',
        self::TARGET_PRODUCTIONS => 'Productions',
        self::TARGET_PRELEVEMENTS => 'Prélèvements',
    ];

    private string $uploadsDir;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KernelInterface $kernel,
        private readonly CaveContext $caveContext,
        private readonly UserRepository $userRepository,
    ) {
        $this->uploadsDir = $this->kernel->getProjectDir().'/uploads';
    }

    public function handle(string $target = self::TARGET_ALL): void
    {
        $cave = $this->caveContext->getCave();
        if (!$cave) {
            throw new \Exception('Cave not found');
        }

        match ($target) {
            self::TARGET_ALL => $this->clearAll($cave),
            self::TARGET_EVENTS => $this->clearEvents($cave),
            self::TARGET_INSCRIPTIONS => $this->clearInscriptions($cave),
            self::TARGET_CONVOCATIONS => $this->clearConvocations($cave),
            self::TARGET_PARCELLES => $this->clearParcelles($cave),
            self::TARGET_PRODUCTIONS => $this->clearProductions($cave),
            self::TARGET_PRELEVEMENTS => $this->clearPrelevements($cave),
            default => throw new \InvalidArgumentException(sprintf('Type de suppression inconnu : %s', $target)),
        };
    }

    private function clearAll(Cave $cave): void
    {
        $this->em->createQuery("DELETE FROM App\Modules\Apport\Domain\Apport e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Ticket\Domain\TicketImport e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();

        // Convocations, Inscriptions, Schedule change requests
        $this->em->createQuery("DELETE FROM App\Modules\ScheduleChange\Domain\ScheduleChange e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Inscription\Domain\Inscription e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Convocation\Domain\Convocation e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // Productions et prelevements
        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\ProductionFinalizationRequest e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->clearPrelevements($cave);
        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\Production e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // Parcelles
        $this->em->createQuery("DELETE FROM App\Modules\Parcelle\Domain\Parcelle e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // Notifications
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Notification e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\EmailMessage e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\InAppMessage e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\SmsMessage e WHERE e.cave = :cave AND e.providerId = 'Test'")->setParameter('cave', $cave)->execute();

        // Events
        $this->em->createQuery("DELETE FROM App\Modules\Event\Domain\Event e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // User mandates
        $this->em->createQuery("DELETE FROM App\Modules\User\Domain\UserMandat e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // Users

        $users = $this->userRepository->findAllForCave($cave);
        foreach ($users as $user) {
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                continue;
            }
            $this->em->remove($user);
        }
        $this->em->flush();

        // Remove all files in the uploads directory and subdirectories
        if (file_exists($this->uploadsDir.'/'.$cave->getSlug())) {
            $this->removeFiles($this->uploadsDir.'/'.$cave->getSlug());
        }
    }

    private function clearEvents(Cave $cave): void
    {
        $this->clearInscriptions($cave);
        $this->em->createQuery("DELETE FROM App\Modules\Event\Domain\Event e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
    }

    private function clearInscriptions(Cave $cave): void
    {
        $convocations = $this->em->createQuery("SELECT IDENTITY(e.convocation) AS id FROM App\Modules\Inscription\Domain\Inscription e WHERE e.cave = :cave AND e.convocation IS NOT NULL")
            ->setParameter('cave', $cave)
            ->getScalarResult();
        $convocationIds = array_column($convocations, 'id');

        $this->em->createQuery("DELETE FROM App\Modules\ScheduleChange\Domain\ScheduleChange e WHERE e.cave = :cave AND e.inscription IS NOT NULL")->setParameter('cave', $cave)->execute();
        if ([] !== $convocationIds) {
            $this->em->createQuery("DELETE FROM App\Modules\Apport\Domain\Apport e WHERE e.cave = :cave AND IDENTITY(e.convocation) IN (:convocations)")
                ->setParameter('cave', $cave)
                ->setParameter('convocations', $convocationIds)
                ->execute();
            $this->em->createQuery("DELETE FROM App\Modules\ScheduleChange\Domain\ScheduleChange e WHERE e.cave = :cave AND IDENTITY(e.convocation) IN (:convocations)")
                ->setParameter('cave', $cave)
                ->setParameter('convocations', $convocationIds)
                ->execute();
        }

        $this->em->createQuery("DELETE FROM App\Modules\Inscription\Domain\Inscription e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();

        if ([] !== $convocationIds) {
            $this->em->createQuery("DELETE FROM App\Modules\Convocation\Domain\Convocation e WHERE e.cave = :cave AND e.id IN (:convocations)")
                ->setParameter('cave', $cave)
                ->setParameter('convocations', $convocationIds)
                ->execute();
        }
    }

    private function clearConvocations(Cave $cave): void
    {
        $this->em->createQuery("DELETE FROM App\Modules\Apport\Domain\Apport e WHERE e.cave = :cave AND e.convocation IS NOT NULL")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\ScheduleChange\Domain\ScheduleChange e WHERE e.cave = :cave AND e.convocation IS NOT NULL")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("UPDATE App\Modules\Inscription\Domain\Inscription e SET e.convocation = NULL WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Convocation\Domain\Convocation e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
    }

    private function clearParcelles(Cave $cave): void
    {
        $this->clearProductions($cave);
        $this->em->createQuery("DELETE FROM App\Modules\Parcelle\Domain\Parcelle e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
    }

    private function clearProductions(Cave $cave): void
    {
        $this->em->createQuery("DELETE FROM App\Modules\Apport\Domain\Apport e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Ticket\Domain\TicketImport e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\ScheduleChange\Domain\ScheduleChange e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Inscription\Domain\Inscription e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Convocation\Domain\Convocation e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\ProductionFinalizationRequest e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->clearPrelevements($cave);
        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\Production e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
    }

    private function clearPrelevements(Cave $cave): void
    {
        $productions = $this->em->createQuery("SELECT e.id FROM App\Modules\Production\Domain\Production e WHERE e.cave = :cave")
            ->setParameter('cave', $cave)
            ->getScalarResult();
        $productionIds = array_column($productions, 'id');

        if ([] === $productionIds) {
            return;
        }

        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\Prelevement e WHERE IDENTITY(e.production) IN (:productions)")
            ->setParameter('productions', $productionIds)
            ->execute();
    }

    private function removeFiles(string $directory): void
    {
        $files = glob($directory.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $subdirectories = glob($directory.'/*', GLOB_ONLYDIR);
        foreach ($subdirectories as $subdirectory) {
            $this->removeFiles($subdirectory);
        }
        rmdir($directory);
    }
}
