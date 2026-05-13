<?php

namespace App\Plugins\DataGenerator\UI\Controller;

use App\Modules\Cave\Infrastructure\Context\CaveContext;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use App\Plugins\DataGenerator\Application\DataGeneratorService;
use App\Plugins\DataGenerator\Application\Read\Handler\ReadDatabaseHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\ClearDataHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\SendTestNotificationsHandler;
use App\Plugins\DataGenerator\Application\Write\Handler\TicketGeneratorHandler;
use App\Plugins\DataGenerator\UI\Form\DataGeneratorForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
                $this->addFlash('danger', 'Erreur lors de la génération des données : ' . $e->getMessage());
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
            $this->addFlash('danger', 'Erreur lors de la suppression des données : ' . $e->getMessage());
        }

        return $this->redirectToRoute('data_generator_index');
    }

    #[Route('/notification', name: 'notification')]
    public function notifications(
        Request $request,
        UserRepository $userRepository,
        CaveContext $caveContext,
        SendTestNotificationsHandler $sendTestNotificationsHandler,
    ): Response {
        $cave = $caveContext->getCave();
        if (!$cave) {
            throw new \LogicException('Cave not found');
        }

        $users = $userRepository->findAllForCave($cave);
        $usersChoices = [
            'Choisir un utilisateur' => null,
        ];
        foreach ($users as $user) {
            if ($user->isSuperAdmin()) {
                continue;
            }
            $usersChoices[$user->getSociete() . ' - ' . $user->getEmail()] = $user->getId();
        }
        $usersChoices['Tous les utilisateurs du cave'] = SendTestNotificationsHandler::ALL_TENANT_USERS_USER_ID;

        $form = $this->createFormBuilder(null, [
            'attr' => [
                'data-turbo' => 'false',
            ],
        ])
            ->add('user', ChoiceType::class, [
                'label' => 'Sélectionner un exploitant',
                'choices' => $usersChoices,
                'required' => true,
            ])
            ->add('number', NumberType::class, [
                'label' => 'Nombre de notifications',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'max' => 10,
                    'step' => 1,
                ],
                'data' => 1,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userId = $data['user'];
            $number = (int) ($data['number'] ?? 1);

            if (null === $userId) {
                $form->get('user')->addError(new FormError('Veuillez sélectionner un utilisateur.'));
            } else {
                try {
                    $result = $sendTestNotificationsHandler->handle($cave, (int) $userId, $number);
                    if (0 === $result['sent']) {
                        $this->addFlash('warning', 'Aucune notification envoyée.');
                    } else {
                        $this->addFlash(
                            'success',
                            sprintf('%d notification(s) envoyée(s) via le module Notification.', $result['sent'])
                        );
                    }
                    foreach ($result['errors'] as $err) {
                        $this->addFlash('warning', $err);
                    }

                    return $this->redirectToRoute('data_generator_notification', status: 303);
                } catch (\Throwable $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'envoi : ' . $e->getMessage());
                }
            }
        }

        return $this->render(
            '@DataGenerator/notifications.html.twig',
            ['form' => $form]
        );
    }

    #[Route('/ticket-generator', name: 'ticket_generator')]
    public function ticketGenerator(
        Request $request,
        UserRepository $userRepository,
        CaveContext $caveContext,
        TicketGeneratorHandler $ticketGeneratorHandler,
    ): Response {
        $cave = $caveContext->getCave();
        if (!$cave) {
            throw new \LogicException('Cave not found');
        }

        $users = $userRepository->findAllForCave($cave);

        $usersChoices = [
            'Choisir un utilisateur' => null,
        ];
        foreach ($users as $user) {
            if ($user->isAdmin()) {
                continue;
            }
            $usersChoices[$user->getSociete() . ' - ' . $user->getEmail()] = $user->getId();
        }
        $usersChoices['Tous les exploitants du cave'] = SendTestNotificationsHandler::ALL_TENANT_USERS_USER_ID;

        $form = $this->createFormBuilder(null, [
            'attr' => [
                'data-turbo' => 'false',
            ],
        ])
            ->add('user', ChoiceType::class, [
                'label' => 'Générer un ticket pour',
                'choices' => $usersChoices,
                'required' => true,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $selectedUserId = $data['user'];

            if (null === $selectedUserId) {
                $form->get('user')->addError(new FormError('Veuillez sélectionner un utilisateur.'));
            } else {
                try {
                    if (SendTestNotificationsHandler::ALL_TENANT_USERS_USER_ID === (int) $selectedUserId) {
                        $caveUserIds = [];
                        foreach ($users as $user) {
                            if ($user->isSuperAdmin()) {
                                continue;
                            }
                            $caveUserIds[] = $user->getId();
                        }
                        $file = $ticketGeneratorHandler->handleAllUsers($cave, $caveUserIds);
                    } else {
                        $file = $ticketGeneratorHandler->handle((int) $selectedUserId, $cave);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de la génération du ticket : ' . $e->getMessage());

                    return $this->redirectToRoute('data_generator_ticket_generator');
                }

                if (null === $file) {
                    $this->addFlash(
                        'warning',
                        SendTestNotificationsHandler::ALL_TENANT_USERS_USER_ID === (int) $selectedUserId
                            ? 'Aucun ticket généré : aucune convocation acceptée trouvée pour les exploitants à la date du jour.'
                            : 'Aucun ticket généré : convocation acceptée introuvable pour cet utilisateur à la date du jour.'
                    );

                    return $this->redirectToRoute('data_generator_ticket_generator', status: 303);
                }

                $response = new BinaryFileResponse($file['path']);
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $file['filename']
                );
                $response->deleteFileAfterSend(true);

                return $response;
            }
        }

        return $this->render('@DataGenerator/ticket_generator.html.twig', [
            'form' => $form,
        ]);
    }
}
