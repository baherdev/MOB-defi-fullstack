<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Station;
use App\Entity\Network;
use App\Entity\NetworkSegment;
use App\Entity\CodeAnalytics;
use App\Entity\Train;
use App\Entity\Trajet;
use App\Entity\TrajetSegment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
  private array $stations = [];
  private array $networks = [];
  private array $codeAnalytics = [];
  private array $trains = [];

  public function load(ObjectManager $manager): void
  {
    // 1. Charger les Code Analytics
    $this->loadCodeAnalytics($manager);

    // 2. Charger les Stations
    $this->loadStations($manager);

    // 3. Charger les Networks
    $this->loadNetworks($manager);

    // 4. Charger les Network Segments
    $this->loadNetworkSegments($manager);

    // 5. Charger des Trains d'exemple
    $this->loadTrains($manager);

    // 6. Créer des trajets d'exemple
    $this->loadExampleTrajets($manager);

    $manager->flush();
  }

  private function loadCodeAnalytics(ObjectManager $manager): void
  {
    $codes = [
      'PASSAGER' => 'Transport de passagers',
      'FRET' => 'Transport de marchandises',
      'MAINTENANCE' => 'Trajet de maintenance',
      'TEST' => 'Trajet de test',
      'TOURISME' => 'Train panoramique touristique'
    ];

    foreach ($codes as $code => $label) {
      $analytics = new CodeAnalytics($code);
      $manager->persist($analytics);
      $this->codeAnalytics[$code] = $analytics;
    }

    echo "✅ Chargé " . count($codes) . " codes analytiques\n";
  }

  private function loadStations(ObjectManager $manager): void
  {
    // Données depuis stations.json
    $stationsData = [
      ['id' => 1, 'shortName' => 'ALLI', 'longName' => 'Allières'],
      ['id' => 2, 'shortName' => 'AVA', 'longName' => 'Les Avants'],
      ['id' => 3, 'shortName' => 'BCHX', 'longName' => 'Bois-de-Chexbres'],
      ['id' => 4, 'shortName' => 'BEMM', 'longName' => 'Belmont-sur-Mx'],
      ['id' => 5, 'shortName' => 'BLB', 'longName' => 'Blankenburg'],
      ['id' => 6, 'shortName' => 'BLON', 'longName' => 'Blonay'],
      ['id' => 7, 'shortName' => 'BODE', 'longName' => 'Boden'],
      ['id' => 8, 'shortName' => 'CABY', 'longName' => 'Chamby'],
      ['id' => 9, 'shortName' => 'CASE', 'longName' => 'Les Cases'],
      ['id' => 10, 'shortName' => 'CAUX', 'longName' => 'Caux'],
      ['id' => 11, 'shortName' => 'CGE', 'longName' => 'Montreux-Collège'],
      ['id' => 12, 'shortName' => 'CHAL', 'longName' => 'Châtelard VD'],
      ['id' => 13, 'shortName' => 'CHAN', 'longName' => 'Chantemerle-près-Blonay'],
      ['id' => 14, 'shortName' => 'CHAU', 'longName' => 'La Chaudanne-Les Moulins'],
      ['id' => 15, 'shortName' => 'CHBL', 'longName' => 'Château-de-Blonay'],
      ['id' => 16, 'shortName' => 'CHCO', 'longName' => 'Chaulin-Cornaux'],
      ['id' => 17, 'shortName' => 'CHER', 'longName' => 'Chernex'],
      ['id' => 18, 'shortName' => 'CHEV', 'longName' => 'Les Chevalleyres'],
      ['id' => 19, 'shortName' => 'CHIZ', 'longName' => 'La Chiésaz'],
      ['id' => 20, 'shortName' => 'CHOE', 'longName' => "Château-d'Oex"],
      ['id' => 21, 'shortName' => 'CHTV', 'longName' => "Château-d'Hauteville"],
      ['id' => 22, 'shortName' => 'CLIE', 'longName' => 'Clies'],
      ['id' => 23, 'shortName' => 'COLD', 'longName' => 'Colondalles'],
      ['id' => 24, 'shortName' => 'COMS', 'longName' => 'Les Combes'],
      ['id' => 25, 'shortName' => 'CRDB', 'longName' => "Crêt-d'y-Bau"],
      ['id' => 26, 'shortName' => 'ECTS', 'longName' => 'Les Echets'],
      ['id' => 27, 'shortName' => 'FAY', 'longName' => 'Fayaux'],
      ['id' => 28, 'shortName' => 'FLED', 'longName' => 'Flendruz'],
      ['id' => 29, 'shortName' => 'FON', 'longName' => 'Fontanivent'],
      ['id' => 30, 'shortName' => 'VVVI', 'longName' => 'Vevey Vignerons'],
      ['id' => 46, 'shortName' => 'MX', 'longName' => 'Montreux'],
      ['id' => 76, 'shortName' => 'VV', 'longName' => 'Vevey'],
      ['id' => 77, 'shortName' => 'ZW', 'longName' => 'Zweisimmen'],
      ['id' => 36, 'shortName' => 'GST', 'longName' => 'Gstaad'],
      ['id' => 43, 'shortName' => 'LENK', 'longName' => 'Lenk im Simmental'],
      ['id' => 45, 'shortName' => 'MTB', 'longName' => 'Montbovon'],
      ['id' => 58, 'shortName' => 'ROSI', 'longName' => 'Rossinière'],
      ['id' => 59, 'shortName' => 'ROU', 'longName' => 'Rougemont'],
      ['id' => 60, 'shortName' => 'SAAN', 'longName' => 'Saanen'],
      ['id' => 64, 'shortName' => 'SDY', 'longName' => 'Sendy-Sollard'],
      ['id' => 65, 'shortName' => 'SONZ', 'longName' => 'Sonzier'],
      ['id' => 78, 'shortName' => 'BOSQ', 'longName' => 'Bosquets'],
      ['id' => 101, 'shortName' => 'SP', 'longName' => 'Spiez'],
      ['id' => 108, 'shortName' => 'IO', 'longName' => 'Interlaken Ost'],
    ];

    foreach ($stationsData as $data) {
      $station = new Station($data['shortName'], $data['longName']);
      $manager->persist($station);
      $this->stations[$data['shortName']] = $station;
    }

    echo "✅ Chargé " . count($stationsData) . " stations\n";
  }

  private function loadNetworks(ObjectManager $manager): void
  {
    $networksData = [
      'MOB' => 'Montreux-Oberland-Bernois',
      'MVR-ce' => 'Montreux-Vevey-Riviera (chemin de fer électrique)'
    ];

    foreach ($networksData as $code => $name) {
      $network = new Network($code);
      $manager->persist($network);
      $this->networks[$code] = $network;
    }

    echo "✅ Chargé " . count($networksData) . " réseaux\n";
  }

  private function loadNetworkSegments(ObjectManager $manager): void
  {
    // Segments du réseau MOB (données depuis distances.json)
    $mobSegments = [
      ['parent' => 'MX', 'child' => 'CGE', 'distance' => 0.65],
      ['parent' => 'CGE', 'child' => 'BEMM', 'distance' => 0.86],
      ['parent' => 'BEMM', 'child' => 'COLD', 'distance' => 0.24],
      ['parent' => 'COLD', 'child' => 'CHAL', 'distance' => 0.40],
      ['parent' => 'CHAL', 'child' => 'FON', 'distance' => 1.13],
      ['parent' => 'FON', 'child' => 'CHER', 'distance' => 1.01],
      ['parent' => 'CHER', 'child' => 'SONZ', 'distance' => 1.18],
      ['parent' => 'SONZ', 'child' => 'CABY', 'distance' => 1.68],
      ['parent' => 'CABY', 'child' => 'SDY', 'distance' => 2.05],
      ['parent' => 'SDY', 'child' => 'AVA', 'distance' => 1.65],
      ['parent' => 'AVA', 'child' => 'MTB', 'distance' => 7.00],
      ['parent' => 'MTB', 'child' => 'ROSI', 'distance' => 7.80],
      ['parent' => 'ROSI', 'child' => 'CHAU', 'distance' => 1.15],
      ['parent' => 'CHAU', 'child' => 'CHOE', 'distance' => 3.29],
      ['parent' => 'CHOE', 'child' => 'ROU', 'distance' => 4.74],
      ['parent' => 'ROU', 'child' => 'SAAN', 'distance' => 4.04],
      ['parent' => 'SAAN', 'child' => 'GST', 'distance' => 2.40],
      ['parent' => 'GST', 'child' => 'ZW', 'distance' => 12.84],
      ['parent' => 'ZW', 'child' => 'LENK', 'distance' => 16.86],
      ['parent' => 'ZW', 'child' => 'SP', 'distance' => 35.00],
      ['parent' => 'SP', 'child' => 'IO', 'distance' => 18.00],
    ];

    $mobNetwork = $this->networks['MOB'];
    $count = 0;

    foreach ($mobSegments as $segmentData) {
      if (!isset($this->stations[$segmentData['parent']]) ||
        !isset($this->stations[$segmentData['child']])) {
        continue;
      }

      $segment = new NetworkSegment(
        $this->stations[$segmentData['parent']],
        $this->stations[$segmentData['child']],
        $segmentData['distance'],
        $mobNetwork
      );
      $manager->persist($segment);
      $count++;

      // Créer le segment inverse (bidirectionnel)
      $segmentReverse = new NetworkSegment(
        $this->stations[$segmentData['child']],
        $this->stations[$segmentData['parent']],
        $segmentData['distance'],
        $mobNetwork
      );
      $manager->persist($segmentReverse);
      $count++;
    }

    // Segments du réseau MVR-ce (exemple simplifié)
    $mvrSegments = [
      ['parent' => 'VV', 'child' => 'BOSQ', 'distance' => 0.53],
      ['parent' => 'BOSQ', 'child' => 'VVVI', 'distance' => 0.97],
      ['parent' => 'VVVI', 'child' => 'CLIE', 'distance' => 0.27],
      ['parent' => 'CLIE', 'child' => 'CHTV', 'distance' => 1.25],
      ['parent' => 'CHTV', 'child' => 'BLON', 'distance' => 1.87],
      ['parent' => 'BLON', 'child' => 'CHAN', 'distance' => 0.97],
      ['parent' => 'CHAN', 'child' => 'CABY', 'distance' => 1.98],
    ];

    $mvrNetwork = $this->networks['MVR-ce'];

    foreach ($mvrSegments as $segmentData) {
      if (!isset($this->stations[$segmentData['parent']]) ||
        !isset($this->stations[$segmentData['child']])) {
        continue;
      }

      $segment = new NetworkSegment(
        $this->stations[$segmentData['parent']],
        $this->stations[$segmentData['child']],
        $segmentData['distance'],
        $mvrNetwork
      );
      $manager->persist($segment);
      $count++;

      // Créer le segment inverse (bidirectionnel)
      $segmentReverse = new NetworkSegment(
        $this->stations[$segmentData['child']],
        $this->stations[$segmentData['parent']],
        $segmentData['distance'],
        $mvrNetwork
      );
      $manager->persist($segmentReverse);
      $count++;
    }

    echo "✅ Chargé $count segments de réseau (bidirectionnels)\n";
  }

  private function loadTrains(ObjectManager $manager): void
  {
    $trainsData = [
      'MOB-001',
      'MOB-002',
      'MOB-GOLDEN-PASS',
      'MVR-101',
      'MVR-102',
    ];

    foreach ($trainsData as $label) {
      $train = new Train($label);
      $manager->persist($train);
      $this->trains[$label] = $train;
    }

    echo "✅ Chargé " . count($trainsData) . " trains\n";
  }

  private function loadExampleTrajets(ObjectManager $manager): void
  {
    // Exemple 1: Montreux → Gstaad (train touristique)
    $this->createSimpleTrajet(
      $manager,
      $this->trains['MOB-GOLDEN-PASS'],
      'MX',
      'GST',
      'TOURISME'
    );

    // Exemple 2: Vevey → Blonay (passagers)
    $this->createSimpleTrajet(
      $manager,
      $this->trains['MVR-101'],
      'VV',
      'BLON',
      'PASSAGER'
    );

    // Exemple 3: Montreux → Château-d'Oex (passagers)
    $this->createSimpleTrajet(
      $manager,
      $this->trains['MOB-001'],
      'MX',
      'CHOE',
      'PASSAGER'
    );

    // Exemple 4: Zweisimmen → Lenk (maintenance)
    $this->createSimpleTrajet(
      $manager,
      $this->trains['MOB-002'],
      'ZW',
      'LENK',
      'MAINTENANCE'
    );

    echo "✅ Créé 4 trajets d'exemple\n";
  }

  private function createSimpleTrajet(
    ObjectManager $manager,
    Train $train,
    string $fromCode,
    string $toCode,
    string $analyticsCode
  ): void {
    $trajet = new Trajet(
      $train,
      $this->stations[$fromCode],
      $this->stations[$toCode],
      $this->codeAnalytics[$analyticsCode]
    );

    // Note: Dans un cas réel, on utiliserait un RoutingService
    // pour calculer le chemin avec Dijkstra et ajouter les TrajetSegments
    // Pour l'instant, on crée juste le trajet sans segments détaillés

    // Distance estimée (à calculer réellement avec l'algorithme)
    $trajet->setDistanceKmTotal($this->estimateDistance($fromCode, $toCode));

    $manager->persist($trajet);
  }

  private function estimateDistance(string $from, string $to): float
  {
    // Distance approximative pour l'exemple
    $distances = [
      'MX-GST' => 50.5,
      'VV-BLON' => 5.86,
      'MX-CHOE' => 35.2,
      'ZW-LENK' => 16.86,
    ];

    $key = "$from-$to";
    return $distances[$key] ?? 10.0;
  }
}
