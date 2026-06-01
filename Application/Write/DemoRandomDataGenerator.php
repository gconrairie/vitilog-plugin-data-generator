<?php

declare(strict_types=1);

namespace App\Plugins\DataGenerator\Application\Write;

final class DemoRandomDataGenerator
{
    private const FIRST_NAMES = [
        'Adrien',
        'Camille',
        'Chloé',
        'Claire',
        'Etienne',
        'Hugo',
        'Julien',
        'Laura',
        'Lucie',
        'Manon',
        'Marc',
        'Nicolas',
        'Paul',
        'Sophie',
        'Thomas',
    ];

    private const LAST_NAMES = [
        'Bernard',
        'Blanc',
        'Bonnet',
        'Dubois',
        'Faure',
        'Fournier',
        'Girard',
        'Lefevre',
        'Martin',
        'Moreau',
        'Petit',
        'Roux',
        'Simon',
        'Thomas',
        'Vincent',
    ];

    private const COMPANY_PREFIXES = [
        'Domaine',
        'Clos',
        'Chateau',
        'EARL',
        'SCEA',
        'GAEC',
    ];

    private const COMPANY_NAMES = [
        'des Coteaux',
        'du Val',
        'de la Roche',
        'des Vignes',
        'du Moulin',
        'de Saint-Martin',
        'des Terrasses',
        'du Soleil',
    ];

    private const CITIES = [
        'Bages',
        'Banyuls-sur-Mer',
        'Cabestany',
        'Canet-en-Roussillon',
        'Collioure',
        'Elne',
        'Latour-Bas-Elne',
        'Perpignan',
        'Rivesaltes',
        'Saint-Cyprien',
        'Saint-Estève',
        'Thuir',
    ];

    private const LIEUX_DITS = [
        'Les Aspres',
        'La Garrigue',
        'Le Mas Vieux',
        'Les Terrasses',
        'La Combe',
        'Le Pla',
        'Les Oliviers',
        'La Serre',
        'Le Moulin',
        'Les Amandiers',
    ];

    public function firstName(): string
    {
        return self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
    }

    public function lastName(): string
    {
        return self::LAST_NAMES[array_rand(self::LAST_NAMES)];
    }

    public function company(): string
    {
        return self::COMPANY_PREFIXES[array_rand(self::COMPANY_PREFIXES)] . ' ' . self::COMPANY_NAMES[array_rand(self::COMPANY_NAMES)];
    }

    public function mobilePhone(): string
    {
        return ''; // '04' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    public function city(): string
    {
        return self::CITIES[array_rand(self::CITIES)];
    }

    public function lieuDit(): string
    {
        return self::LIEUX_DITS[array_rand(self::LIEUX_DITS)];
    }

    public function numberBetween(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
