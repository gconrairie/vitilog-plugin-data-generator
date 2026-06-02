<?php

namespace App\Plugins\DataGenerator\UI\Controller;

use App\Modules\Cave\Infrastructure\Context\CaveContext;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use App\Plugins\DataGenerator\Application\DataGeneratorService;
use App\Plugins\DataGenerator\Application\Read\Handler\ReadDatabaseHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\ClearDataHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\TicketGeneratorHandler;
use App\Plugins\DataGenerator\UI\Form\DataGeneratorForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/plugin/data-generator', name: 'data_generator_')]
class DataGeneratorController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        DataGeneratorService $dataGeneratorService,
        DataGeneratorForm $dataGeneratorForm,
        CaveContext $caveContext,
        ReadDatabaseHandler $readDatabaseHandler,
    ): Response {
        $cave = $caveContext->getCave();
        if (!$cave) {
            throw new \Exception('Cave not found');
        }

        $counters = $readDatabaseHandler->handle($cave);

        $form = $dataGeneratorForm->buildForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $response = $dataGeneratorService->handleForm($data, $cave);
                $messages = [];
                foreach ($response as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    foreach ($item as $message) {
                        if (null === $message || '' === $message) {
                            continue;
                        }
                        $messages[] = $message;
                    }
                }

                if ([] !== $messages) {
                    $this->addFlash('success', implode('<br>', $messages));
                } else {
                    $this->addFlash('success', 'Génération terminée.');
                }

                // Turbo (et en général) exige une redirection après POST (PRG pattern)
                return $this->redirectToRoute('data_generator_index', status: 303);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la génération des données : '.$e->getMessage());
            }
        }

        return $this->render(
            '@DataGenerator/index.html.twig',
            [
                'generator_form' => $form,

                'counters' => $counters,
            ]
        );
    }

    #[Route('/clear', name: 'clear')]
    public function clearData(
        ClearDataHandler $clearDataHandler,
    ) {
        try {
            $clearDataHandler->handle();
            $this->addFlash('success', 'Données supprimées avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la suppression des données : '.$e->getMessage());
        }

        return $this->redirectToRoute('data_generator_index');
    }

    #[Route('/ticket-generator', name: 'ticket_generator')]
    public function ticketGenerator(
        UserRepository $userRepository,
        CaveContext $caveContext,
        TicketGeneratorHandler $ticketGeneratorHandler,
    ): Response {
        $cave = $caveContext->getCave();
        $users = $userRepository->findAllForCave($cave);

        $filteredUsers = array_filter($users, function ($user) {
            return !$user->isAdmin() && !$user->isSuperAdmin();
        });

        $file = null;
        try {
            $file = $ticketGeneratorHandler->handle($cave, $filteredUsers);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la génération des tickets : '.$e->getMessage());

            return $this->redirectToRoute('data_generator_index');
        }

        if (null === $file) {
            $this->addFlash(
                'warning',
                'Aucun ticket généré : aucune convocation acceptée trouvée pour les exploitants à la date du jour.'
            );

            return $this->redirectToRoute('data_generator_index');
        }
        try {
            $response = $this->file(
                $file['path'],
                $file['filename'],
                ResponseHeaderBag::DISPOSITION_ATTACHMENT
            );
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la préparation du téléchargement : '.$e->getMessage());

            return $this->redirectToRoute('data_generator_index');
        }
    }
}
