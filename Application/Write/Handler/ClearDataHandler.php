<?php

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\User\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ClearDataHandler
{
    private string $uploadsDir;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KernelInterface $kernel,
    ) {
        $this->uploadsDir = $this->kernel->getProjectDir().'/uploads';
    }

    public function handle(): void
    {
        // Apports
        $this->em->createQuery("DELETE FROM App\Modules\Apport\Domain\Apport e")->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Ticket\Domain\TicketImport e")->execute();

        // Convocations
        $this->em->createQuery("DELETE FROM App\Modules\Inscription\Domain\Inscription e")->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Convocation\Domain\Convocation e")->execute();
        // Productions et prelevements
        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\Prelevement e")->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Production\Domain\Production e")->execute();
        // Parcelles
        $this->em->createQuery("DELETE FROM App\Modules\Parcelle\Domain\Parcelle e")->execute();
        // Notifications
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Notification e")->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\EmailMessage e")->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\InAppMessage e")->execute();
        $this->em->createQuery("DELETE FROM App\Modules\Notification\Domain\Message\SmsMessage e")->execute();

        // Events
        $this->em->createQuery("DELETE FROM App\Modules\Event\Domain\Event e")->execute();
        // User mandates
        $this->em->createQuery("DELETE FROM App\Modules\User\Domain\UserMandat e")->execute();
        // Users

        $users = $this->em->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                continue;
            }
            $this->em->remove($user);
        }
        $this->em->flush();

        // Remove all files in the uploads directory and subdirectories
        if (file_exists($this->uploadsDir)) {
            $this->removeFiles($this->uploadsDir);
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
