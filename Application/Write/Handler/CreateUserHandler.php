<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\User\Application\User\Write\Dto\UserWriteDto;
use App\Modules\User\Application\User\Write\Handler\CreateUserHandler as ModuleCreateUserHandler;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use App\Plugins\DataGenerator\Application\Read\Dto\DataGenerationDto;
use App\Shared\Security\ActorContextProvider;
use Faker\Factory as Faker;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ModuleCreateUserHandler $createUserHandler,
        private readonly ActorContextProvider $actorContextProvider,
        private readonly SluggerInterface $slugger,
    ) {}

    public function handleExploitant(DataGenerationDto $dto): ?array
    {
        if ($dto->idExploitant < 0) {
            return null;
        }

        // Set variables
        $userId = $dto->idExploitant;
        $email = $dto->email;
        $roles = $dto->isPrestataire ? ['ROLE_EXPLOITANT', 'ROLE_PRESTATAIRE'] : ['ROLE_EXPLOITANT'];
        $response = $this->handle($dto, $userId, $roles, $email);
        $dto->exploitant = $response['user'];

        return $this->response($response, 'exploitant');
    }

    public function handlePrestataire(DataGenerationDto $dto): ?array
    {
        if ($dto->idPrestataire < 0) {
            return null;
        }

        // Set variables
        $userId = $dto->idPrestataire;
        $email = $dto->emailPrestataire;
        $roles = ['ROLE_EXPLOITANT', 'ROLE_PRESTATAIRE'];
        $response = $this->handle($dto, $userId, $roles, $email);
        $dto->prestataire = $response['user'];

        return $this->response($response, 'prestataire');
    }

    private function response(array $response, string $type): ?array
    {
        $user = $response['user'];
        $status = $response['status'];
        $response = 'update' === $status ? 'Mise à jour ' : 'Création ';
        $response .= 'exploitant' === $type ? 'de l\'exploitant' : 'du prestataire';

        return [$response . " {$user->getPrenom()} {$user->getNom()} - {$user->getEmail()} - {$user->getCode()}"];
    }

    public function handle(DataGenerationDto $dto, int $userId, array $roles, ?string $email = null): ?array
    {
        $faker = Faker::create('fr_FR');

        $firstName = (string) $faker->firstName();
        $lastName = (string) $faker->lastName();
        $societe = (string) $faker->company();
        $code = (string) random_int(1000, 9999);
        $email = $email ?? $this->slugger->slug($firstName . '.' . $lastName)->lower() . '.' . $code . '@vitilog.fr';
        // Crée un telephone mobile FR ()
        $telephone = '06' . $faker->randomNumber(8, true);

        $actorContext = $this->actorContextProvider->get();
        // $roles = $dto->isPrestataire ? ['ROLE_EXPLOITANT', 'ROLE_PRESTATAIRE'] : ['ROLE_EXPLOITANT'];

        if ($userId > 0) {
            $user = $this->userRepository->find($userId);
            if ($dto->exploitant) {
                $dto->exploitant->setRoles($roles);
                $this->userRepository->save($dto->exploitant);
            }

            return [
                'user' => $user,
                'status' => 'update',
            ];
        }
        $user = $this->createUserHandler->handle(
            actorContext: $actorContext,
            cave: $dto->cave,
            userDto: new UserWriteDto(
                email: $email,
                code: $code,
                nom: $lastName,
                prenom: $firstName,
                societe: $societe,
                telephone: $telephone,
                roles: $roles,
            ),
        );

        return [
            'user' => $user,
            'status' => 'create',
        ];
    }
}
