<?php

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Cave\Infrastructure\Context\CaveContext;
use App\Modules\User\Domain\User;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ClearDataHandler
{
    private string $uploadsDir;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KernelInterface $kernel,
        private readonly CaveContext $caveContext,
        private readonly UserRepository $userRepository,
    ) {
        $this->uploadsDir = $this->kernel->getProjectDir().'/uploads';
    }

    public function handle(): void
    {
        $cave = $this->caveContext->getCave();
        if (!$cave) {
            throw new \Exception('Cave not found');
        }
        // Apports
        $this->em->createQuery("DELETE FROM App\Modules\Apport\Domain\Apport e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Ticket\Domain\TicketImport e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();

        // Convocations, Inscriptions, Schedule change requests
        $this->em->createQuery("DELETE FROM App\Modules\ScheduleChange\Domain\ScheduleChange e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Inscription\Domain\Inscription e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Convocation\Domain\Convocation e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // Productions et prelevements
        // $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\Prelevement e WHERE e.cave = :cave")->setParameter()->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\Production e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // Parcelles
        $this->em->createQuery("DELETE FROM App\Modules\Parcelle\Domain\Parcelle e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        // Notifications
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Notification e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\EmailMessage e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\InAppMessage e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\SmsMessage e WHERE e.cave = :cave")->setParameter('cave', $cave)->execute();

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
