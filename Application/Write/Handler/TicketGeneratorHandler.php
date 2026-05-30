<?php

namespace App\Plugins\DataGenerator\Application\Write\Handler;

use App\Modules\Cave\Domain\Cave;
use App\Modules\Convocation\Domain\Convocation;
use App\Modules\Convocation\Domain\Enum\ConvocationResponse;
use App\Modules\Convocation\Domain\Enum\ConvocationStatus;
use App\Modules\Convocation\Infrastructure\Repository\ConvocationRepository;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use Symfony\Component\HttpKernel\KernelInterface;

class TicketGeneratorHandler
{
    private const MAX_CHUNKS_KG = 3200;

    private array $ticketData = [];
    private string $projectDir;
    private string $dummyDir = '/var/tickets/dummy/';

    public function __construct(
        KernelInterface $kernel,
        private readonly UserRepository $userRepository,
        private readonly ConvocationRepository $convocationRepository,
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }

    /**
     * @return array{path: string, filename: string}|null
     */
    public function handle(Cave $cave, array $userIds): ?array
    {
        $this->ticketData = [];
        $lastLineNumber = 0;

        foreach ($userIds as $userId) {
            try {
                $convocations = $this->findConvocations((int) $userId, $cave);
                $deliverable = array_filter($convocations, function (Convocation $convocation) {
                    return ConvocationStatus::PENDING == $convocation->getStatus()
                        && ConvocationResponse::ACCEPTED == $convocation->getResponseStatus();
                });
            } catch (\Exception) {
                continue;
            }
            if (empty($deliverable)) {
                continue;
            }
            $lastLineNumber = $this->parseConvocations($deliverable, $lastLineNumber);
        }

        if (empty($this->ticketData)) {
            return null;
        }

        try {
            return $this->createTicketFile('dummy_tickets_' . date('Y-m-d_H-i-s') . '.txt');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse user parcelles and generate ticket data.
     */
    private function findConvocations(int $userId, Cave $cave): array
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }
        $convocations = $this->convocationRepository->findAllForUserInRange($user, new \DateTimeImmutable('now'), new \DateTimeImmutable('tomorrow'), $cave);

        return $convocations;
    }

    /**
     * @return int dernier numéro de ligne attribué (pour enchaîner plusieurs exploitants dans un même fichier)
     */
    private function parseConvocations(array $convocations, int $previousMaxLine = 0): int
    {
        $line = $previousMaxLine;
        foreach ($convocations as $convocation) {
            if (ConvocationResponse::ACCEPTED !== $convocation->getResponseStatus()) {
                continue;
            }
            $date = $convocation->getDateConvocation()->format('d-m-Y');
            $heure = $convocation->getHeureDebut()->format('H:i:s');
            $quai = $convocation->getLieuLivraison();
            $codeUser = $convocation->getProduction()->getParcelle()->getActiveManager()->getCode();
            $codeCepage = $convocation->getProduction()->getParcelle()->getCepage()->getId();
            $numeroParcelle = $convocation->getProduction()->getParcelle()->getNumero();
            $numeroVoie = 'V' . rand(1, 100); // numero_voie
            $numeroPressoir = 'P' . rand(1, 100); // numero_pressoir

            $quantiteDemandeeKg = $convocation->getQuantiteDemandeeKg();
            $quantiteLivreeKg = 0;
            do {
                $chunkSize = random_int(1800, self::MAX_CHUNKS_KG);

                $this->ticketData[] = [
                    'numero_ligne' => ++$line, // numero_ligne
                    'date' => $date,
                    'heure' => $heure,
                    'quai' => $quai,
                    'code_user' => $codeUser,
                    'code_cepage' => $codeCepage,
                    'quantite_kg' => $chunkSize,
                    'degre' => 0,
                    'numero_voie' => $numeroVoie,
                    'numero_pressoir' => $numeroPressoir,
                    'code_parcelle' => $numeroParcelle,
                ];
                $quantiteLivreeKg += $chunkSize;
            } while ($quantiteLivreeKg < $quantiteDemandeeKg);
        }

        return $line;
    }

    /**
     * @return array{path: string, filename: string}
     *
     * @throws \Exception
     */
    private function createTicketFile(?string $fileName = null): array
    {
        $fileName ??= 'dummy_ticket_' . date('Y-m-d_H-i-s') . '.txt';
        $filePath = $this->projectDir . $this->dummyDir . $fileName;

        if (!is_dir($this->projectDir . $this->dummyDir)) {
            mkdir($this->projectDir . $this->dummyDir, 0777, true);
        } else {
            $files = glob($this->projectDir . $this->dummyDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        $file = fopen($filePath, 'w');
        if ($file) {
            fwrite($file, $this->buildTicketFileContent());
            fclose($file);
        } else {
            throw new \Exception('Unable to create file');
        }

        return ['path' => $filePath, 'filename' => $fileName];
    }

    private function buildTicketFileContent(): string
    {
        $content = '' . PHP_EOL;
        foreach ($this->ticketData as $line) {
            $data = implode("\t", [
                $line['numero_ligne'],
                $line['date'],
                $line['heure'],
                $line['quai'],
                $line['code_user'],
                '',
                $line['code_cepage'],
                '',
                $line['quantite_kg'],
                $line['degre'],
                $line['numero_voie'],
                $line['numero_pressoir'],
                $line['code_parcelle'],
            ]);
            $content .= $data . "\r\n";
        }

        return $content;
    }
}
