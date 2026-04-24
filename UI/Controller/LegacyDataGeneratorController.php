<?php

namespace App\Plugins\DataGenerator\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyDataGeneratorController extends AbstractController
{
    #[Route('/admin/plugin/datagenerator', name: 'data_generator_legacy_index', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->redirectToRoute('data_generator_index', status: 303);
    }
}
