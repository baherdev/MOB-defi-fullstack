<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127221924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE code_analytics (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_46879EB0EA750E8 (label), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE network_segments (id INT AUTO_INCREMENT NOT NULL, distance_km NUMERIC(10, 2) NOT NULL, parent_station_id INT NOT NULL, child_station_id INT NOT NULL, network_id INT NOT NULL, INDEX IDX_8B0DE0B866FE3665 (parent_station_id), INDEX IDX_8B0DE0B8C5624C5E (child_station_id), INDEX IDX_8B0DE0B834128B91 (network_id), INDEX idx_segment_stations (parent_station_id, child_station_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE networks (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_D9B9B42B5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE stations (id INT AUTO_INCREMENT NOT NULL, short_name VARCHAR(10) NOT NULL, long_name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_A7F775E93EE4B093 (short_name), INDEX idx_station_short_name (short_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE trains (id INT AUTO_INCREMENT NOT NULL, train_label VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE trajet_segments (id INT AUTO_INCREMENT NOT NULL, sequence_order INT NOT NULL, trajet_id INT NOT NULL, network_segment_id INT NOT NULL, INDEX IDX_71D4D90DD12A823 (trajet_id), INDEX IDX_71D4D90D2789511 (network_segment_id), INDEX idx_trajet_sequence (trajet_id, sequence_order), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE trajets (id INT AUTO_INCREMENT NOT NULL, distance_km_total NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, train_id INT NOT NULL, station_dep_id INT NOT NULL, station_arriv_id INT NOT NULL, code_analytics_id INT NOT NULL, INDEX IDX_FF2B5BA923BCD4D0 (train_id), INDEX IDX_FF2B5BA94EA8BB8A (station_dep_id), INDEX IDX_FF2B5BA98B923E8C (station_arriv_id), INDEX idx_trajet_created_at (created_at), INDEX idx_trajet_code_analytics (code_analytics_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE network_segments ADD CONSTRAINT FK_8B0DE0B866FE3665 FOREIGN KEY (parent_station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE network_segments ADD CONSTRAINT FK_8B0DE0B8C5624C5E FOREIGN KEY (child_station_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE network_segments ADD CONSTRAINT FK_8B0DE0B834128B91 FOREIGN KEY (network_id) REFERENCES networks (id)');
        $this->addSql('ALTER TABLE trajet_segments ADD CONSTRAINT FK_71D4D90DD12A823 FOREIGN KEY (trajet_id) REFERENCES trajets (id)');
        $this->addSql('ALTER TABLE trajet_segments ADD CONSTRAINT FK_71D4D90D2789511 FOREIGN KEY (network_segment_id) REFERENCES network_segments (id)');
        $this->addSql('ALTER TABLE trajets ADD CONSTRAINT FK_FF2B5BA923BCD4D0 FOREIGN KEY (train_id) REFERENCES trains (id)');
        $this->addSql('ALTER TABLE trajets ADD CONSTRAINT FK_FF2B5BA94EA8BB8A FOREIGN KEY (station_dep_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE trajets ADD CONSTRAINT FK_FF2B5BA98B923E8C FOREIGN KEY (station_arriv_id) REFERENCES stations (id)');
        $this->addSql('ALTER TABLE trajets ADD CONSTRAINT FK_FF2B5BA93F018A71 FOREIGN KEY (code_analytics_id) REFERENCES code_analytics (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE network_segments DROP FOREIGN KEY FK_8B0DE0B866FE3665');
        $this->addSql('ALTER TABLE network_segments DROP FOREIGN KEY FK_8B0DE0B8C5624C5E');
        $this->addSql('ALTER TABLE network_segments DROP FOREIGN KEY FK_8B0DE0B834128B91');
        $this->addSql('ALTER TABLE trajet_segments DROP FOREIGN KEY FK_71D4D90DD12A823');
        $this->addSql('ALTER TABLE trajet_segments DROP FOREIGN KEY FK_71D4D90D2789511');
        $this->addSql('ALTER TABLE trajets DROP FOREIGN KEY FK_FF2B5BA923BCD4D0');
        $this->addSql('ALTER TABLE trajets DROP FOREIGN KEY FK_FF2B5BA94EA8BB8A');
        $this->addSql('ALTER TABLE trajets DROP FOREIGN KEY FK_FF2B5BA98B923E8C');
        $this->addSql('ALTER TABLE trajets DROP FOREIGN KEY FK_FF2B5BA93F018A71');
        $this->addSql('DROP TABLE code_analytics');
        $this->addSql('DROP TABLE network_segments');
        $this->addSql('DROP TABLE networks');
        $this->addSql('DROP TABLE stations');
        $this->addSql('DROP TABLE trains');
        $this->addSql('DROP TABLE trajet_segments');
        $this->addSql('DROP TABLE trajets');
    }
}
