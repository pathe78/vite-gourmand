<?php

namespace App\Service;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\BSON\UTCDateTime;
use Psr\Log\LoggerInterface;

class MongoDBService
{
    private ?Client $client = null;

    public function __construct(
        private string $mongoUrl,
        private string $mongoDb,
        private LoggerInterface $logger
    ) {
    }

    // ── Récupérer une collection (connexion ouverte seulement au premier besoin) ──
    public function getCollection(string $collectionName): Collection
    {
        if ($this->client === null) {
            $this->client = new Client($this->mongoUrl, [], [
                'serverSelectionTimeoutMS' => 3000,
            ]);
        }

        return $this->client->selectDatabase($this->mongoDb)->selectCollection($collectionName);
    }

    // ── Enregistrer une stat de commande ──
    public function enregistrerCommande(int $menuId, string $titreMenu, float $montant): void
    {
        // Une panne de MongoDB ne doit pas empêcher la commande (déjà enregistrée en MySQL)
        try {
            $this->getCollection('stats_commandes')->insertOne([
                'menu_id'    => $menuId,
                'titre_menu' => $titreMenu,
                'montant'    => $montant,
                'date'       => new UTCDateTime(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('MongoDB indisponible : ' . $e->getMessage());
        }
    }

    // ── Stats par menu ──
    public function getStatsByMenu(): array
    {
        $collection = $this->getCollection('stats_commandes');

        $pipeline = [
            [
                '$group' => [
                    '_id'             => '$menu_id',
                    'titre_menu'      => ['$first' => '$titre_menu'],
                    'nb_commandes'    => ['$sum' => 1],
                    'chiffre_affaires' => ['$sum' => '$montant'],
                ]
            ],
            ['$sort' => ['nb_commandes' => -1]]
        ];

        $result = $collection->aggregate($pipeline)->toArray();

        return array_map(function ($item) {
            return [
                'menuId'          => $item['_id'],
                'titreMenu'       => $item['titre_menu'],
                'nbCommandes'     => $item['nb_commandes'],
                'chiffreAffaires' => $item['chiffre_affaires'],
            ];
        }, $result);
    }

    // ── Stats par période ──
    public function getStatsByPeriode(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        $collection = $this->getCollection('stats_commandes');

        $pipeline = [
            [
                '$match' => [
                    'date' => [
                        '$gte' => new UTCDateTime($dateDebut->getTimestamp() * 1000),
                        '$lte' => new UTCDateTime($dateFin->getTimestamp() * 1000),
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id'              => '$menu_id',
                    'titre_menu'       => ['$first' => '$titre_menu'],
                    'nb_commandes'     => ['$sum' => 1],
                    'chiffre_affaires' => ['$sum' => '$montant'],
                ]
            ],
            ['$sort' => ['nb_commandes' => -1]]
        ];

        $result = $collection->aggregate($pipeline)->toArray();

        return array_map(function ($item) {
            return [
                'menuId'          => $item['_id'],
                'titreMenu'       => $item['titre_menu'],
                'nbCommandes'     => $item['nb_commandes'],
                'chiffreAffaires' => $item['chiffre_affaires'],
            ];
        }, $result);
    }
}