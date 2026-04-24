<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Cave\Domain\Cave;
use App\Modules\Notification\Application\Notification\Write\Dto\NotificationPayloadDto;
use App\Modules\Notification\Application\NotificationService;
use App\Modules\User\Domain\User;
use App\Modules\User\Infrastructure\Repository\UserRepository;

/**
 * Envoie des notifications de test via le module Notification (canaux selon préférences utilisateur).
 */
final class SendTestNotificationsHandler
{
    public const ALL_TENANT_USERS_USER_ID = 999;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @return array{sent: int, errors: list<string>}
     */
    public function handle(Cave $cave, int $userId, int $number): array
    {
        $number = max(1, min(100, $number));

        $recipients = $this->resolveRecipients($cave, $userId);
        if ([] === $recipients) {
            return ['sent' => 0, 'errors' => ['Aucun destinataire valide pour ce cave.']];
        }

        $errors = [];
        $sent = 0;
        $notificationType = 'notification.data_generator';

        foreach ($recipients as $recipient) {
            for ($index = 0; $index < $number; ++$index) {
                $title = sprintf('Notification de test %d', $index + 1);
                $message = 'Ceci est une notification de test générée par le plugin **DataGenerator**.';

                $dto = new NotificationPayloadDto(
                    cave: $cave,
                    recipient: $recipient,
                    title: $title,
                    message: $message,
                );

                $channelErrors = $this->notificationService->send($dto, $notificationType, false, []);
                foreach ($channelErrors as $err) {
                    $errors[] = sprintf('%s — %s', $recipient->getEmail() ?? (string) $recipient->getId(), $err);
                }
                ++$sent;
            }
        }

        return ['sent' => $sent, 'errors' => $errors];
    }

    /**
     * @return list<User>
     */
    private function resolveRecipients(Cave $cave, int $userId): array
    {
        if (self::ALL_TENANT_USERS_USER_ID === $userId) {
            $users = $this->userRepository->findAllForCave($cave);

            return array_values(array_filter(
                $users,
                static fn (User $u): bool => !$u->isSuperAdmin(),
            ));
        }

        $user = $this->userRepository->findOneByIdAndCave($userId, $cave);
        if (null === $user || $user->isSuperAdmin()) {
            return [];
        }

        return [$user];
    }
}
