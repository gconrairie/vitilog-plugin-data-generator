<?php

namespace App\Plugins\DataGenerator\UI\Form;

use App\Modules\Cave\Infrastructure\Context\CaveContext;
use App\Modules\Cepage\Domain\Cepage;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use App\Shared\Form\Type\SwitchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class DataGeneratorForm
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private CaveContext $caveContext,
    ) {
    }

    public function buildForm(): FormInterface
    {
        $cave = $this->caveContext->getCave();
        $allUsers = $this->userRepository->findAllForCave($cave);
        $exploitantChoices = [
            'Aucun' => -1,
            'Nouvel exploitant' => 0,
        ];

        $prestataireChoices = [
            'Aucun' => -1,
            'Nouveau prestataire' => 0,
        ];

        foreach ($allUsers as $user) {
            if ($user->isExploitant()) {
                $exploitantChoices[$user->getEmail()] = $user->getId();
            }
            if ($user->isPrestataire()) {
                $prestataireChoices[$user->getEmail()] = $user->getId();
            }
        }

        $cepages = $this->entityManager->getRepository(Cepage::class)->findAll();
        $cepagesChoices = [
            'Au hasard' => null,
        ];
        foreach ($cepages as $cepage) {
            $cepagesChoices[$cepage->getNom()] = $cepage->getId();
        }

        $formBuilder = $this->formFactory->createBuilder();

        $formBuilder
            // Exploitant
            ->add('exploitant', ChoiceType::class, [
                'label' => 'Sélectionner un exploitant',
                'choices' => $exploitantChoices,
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'exploitant',
                ],
                'data' => 0,
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Laisser vide pour un email aléatoire',
                    'data-control' => 'dg-fixture',
                    'data-field' => 'exploitant-email',
                ],
            ])
            ->add('isPrestataire', SwitchType::class, [
                'label' => 'Est aussi prestataire',
                'required' => false,
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'isPrestataire',
                ],
            ])

            // Prestataire
            ->add('prestataire', ChoiceType::class, [
                'label' => 'Sélectionner un prestataire',
                'choices' => $prestataireChoices,
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'prestataire',
                ],
                'data' => null,
            ])
            ->add('emailPrestataire', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Laisser vide pour un email aléatoire',
                    'data-control' => 'dg-fixture',
                    'data-field' => 'prestataire-email',
                ],
            ])

            // Parcelles
            ->add('parcelles', NumberType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                    'step' => 1,
                    'data-control' => 'dg-fixture',
                    'data-field' => 'parcelles',
                ],
                'required' => false,
                'data' => 1,
            ])
            ->add('cepages', ChoiceType::class, [
                'label' => 'Cepages',
                'choices' => $cepagesChoices,
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'cepages',
                ],
            ])

            // Production
            ->add('production', SwitchType::class, [
                'label' => 'Production',
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'production',
                ],
                'required' => false,
            ])

            ->add('prelevement', SwitchType::class, [
                'label' => 'Prélèvements',
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'prelevement',
                ],
                'required' => false,
            ])

            // Convocation
            ->add('convocation', SwitchType::class, [
                'label' => 'Convocation',
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'convocation',
                ],
                'required' => false,
            ])
            ->add('accept', SwitchType::class, [
                'label' => 'Acceptée',
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'accept',
                ],
                'required' => false,
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'required' => false,
                'data' => null, // new \DateTime()->modify(('+1 day')),
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'date',
                ],
            ])

            // Inscription
            ->add('inscription', SwitchType::class, [
                'label' => 'Inscription',
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'inscription',
                ],
                'required' => false,
            ])
            ->add('inscriptionRequest', SwitchType::class, [
                'label' => 'Demande envoyée',
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'inscription-request',
                ],
                'required' => false,
            ])
            ->add('acceptInscription', SwitchType::class, [
                'label' => 'Acceptée',
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'accept-inscription',
                ],
                'required' => false,
            ])

            // Options
            ->add('events', SwitchType::class, [
                'label' => 'Générer un calendrier d\'apports',
                'required' => false,
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'events',
                ],
            ])
            ->add('passed', SwitchType::class, [
                'label' => 'Générer des données passées ('.(new \DateTime())->modify('-1 year')->format('Y').')',
                'required' => false,
                'attr' => [
                    'data-control' => 'dg-fixture',
                    'data-field' => 'passed',
                ],
            ]);

        // Return the built form
        return $formBuilder->getForm();
    }
}
