<?php

namespace App\DataFixtures;

use App\Entity\Dossier;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures de données pour l'application M-Motors.
 * Crée un jeu de données réaliste pour les démonstrations.
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Création du compte admin
        $admin = new User();
        $admin->setEmail('admin@mmotors.fr');
        $admin->setFirstName('Admin');
        $admin->setLastName('M-Motors');
        $admin->setPhone('0600000000');
        $admin->setIsAdmin(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin1234!'));
        $manager->persist($admin);

        // Création des comptes clients
        $clients = [];
        $clientsData = [
            ['jean.dupont@email.fr', 'Jean', 'Dupont', '0612345678'],
            ['marie.martin@email.fr', 'Marie', 'Martin', '0623456789'],
            ['pierre.bernard@email.fr', 'Pierre', 'Bernard', '0634567890'],
            ['sophie.leblanc@email.fr', 'Sophie', 'Leblanc', '0645678901'],
        ];

        foreach ($clientsData as $data) {
            $client = new User();
            $client->setEmail($data[0]);
            $client->setFirstName($data[1]);
            $client->setLastName($data[2]);
            $client->setPhone($data[3]);
            $client->setIsAdmin(false);
            $client->setRoles(['ROLE_USER']);
            $client->setPassword($this->passwordHasher->hashPassword($client, 'Client1234!'));
            $manager->persist($client);
            $clients[] = $client;
        }

        // Création des véhicules
        $vehiclesData = [
            ['Renault', 'Clio', 2021, 35000, 12500, 'sale', 'Renault Clio en excellent etat, entretien a jour.'],
            ['Peugeot', '308', 2020, 42000, 16800, 'sale', 'Peugeot 308 break, tres spacieuse.'],
            ['Volkswagen', 'Golf', 2022, 18000, 22000, 'sale', 'Golf 8 quasi neuve, toutes options.'],
            ['Toyota', 'Yaris', 2021, 28000, 14500, 'sale', 'Toyota Yaris hybride, faible consommation.'],
            ['Ford', 'Focus', 2019, 55000, 13200, 'sale', 'Ford Focus diesel, ideale pour les longs trajets.'],
            ['Renault', 'Megane', 2022, 12000, 285, 'rental', 'Renault Megane en location longue duree.'],
            ['Peugeot', '2008', 2021, 22000, 320, 'rental', 'Peugeot 2008 SUV, confort optimal.'],
            ['Citroen', 'C3', 2020, 38000, 199, 'rental', 'Citroen C3, citadine economique en LLD.'],
            ['BMW', 'Serie 1', 2022, 15000, 450, 'rental', 'BMW Serie 1 premium en location.'],
            ['Mercedes', 'Classe A', 2021, 25000, 480, 'rental', 'Mercedes Classe A, luxe accessible en LLD.'],
            ['Nissan', 'Juke', 2020, 45000, 17500, 'sale', 'Nissan Juke crossover, tres agreable.'],
            ['Hyundai', 'Tucson', 2022, 20000, 26000, 'sale', 'Hyundai Tucson SUV hybride, garantie constructeur.'],
        ];

        $vehicles = [];
        foreach ($vehiclesData as $data) {
            $vehicle = new Vehicle();
            $vehicle->setBrand($data[0]);
            $vehicle->setModel($data[1]);
            $vehicle->setYear($data[2]);
            $vehicle->setKilometrage($data[3]);
            $vehicle->setPrice($data[4]);
            $vehicle->setType($data[5]);
            $vehicle->setDescription($data[6]);
            $vehicle->setIsAvailable(true);
            $manager->persist($vehicle);
            $vehicles[] = $vehicle;
        }

        $manager->flush();

        // Création des dossiers avec statuts variés
        $dossiersData = [
            [$clients[0], $vehicles[0], 'purchase', 'pending'],
            [$clients[0], $vehicles[5], 'rental', 'approved'],
            [$clients[1], $vehicles[2], 'purchase', 'in_progress'],
            [$clients[1], $vehicles[6], 'rental', 'pending'],
            [$clients[2], $vehicles[1], 'purchase', 'rejected'],
            [$clients[2], $vehicles[7], 'rental', 'approved'],
            [$clients[3], $vehicles[3], 'purchase', 'in_progress'],
            [$clients[3], $vehicles[8], 'rental', 'pending'],
        ];

        foreach ($dossiersData as $data) {
            $dossier = new Dossier();
            $dossier->setClient($data[0]);
            $dossier->setVehicle($data[1]);
            $dossier->setType($data[2]);
            $dossier->setStatus($data[3]);
            $dossier->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 30) . ' days'));
            if ($data[3] !== 'pending') {
                $dossier->setUpdatedAt(new \DateTimeImmutable('-' . rand(1, 10) . ' days'));
            }
            if ($data[3] === 'rejected') {
                $dossier->setComment('Dossier incomplet, pieces justificatives manquantes.');
            }
            $manager->persist($dossier);
        }

        $manager->flush();
    }
}
