<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523105447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, type VARCHAR(50) NOT NULL, uploaded_at DATETIME NOT NULL, dossier_id INT NOT NULL, INDEX IDX_D8698A76611C0C56 (dossier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dossier (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, comment LONGTEXT DEFAULT NULL, client_id INT NOT NULL, vehicle_id INT NOT NULL, INDEX IDX_3D48E03719EB6921 (client_id), INDEX IDX_3D48E037545317D1 (vehicle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, is_admin TINYINT NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, zip_code VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vehicle (id INT AUTO_INCREMENT NOT NULL, brand VARCHAR(100) NOT NULL, model VARCHAR(100) NOT NULL, year INT NOT NULL, kilometrage INT NOT NULL, price DOUBLE PRECISION NOT NULL, type VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, photo_url VARCHAR(255) DEFAULT NULL, is_available TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03719EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76611C0C56');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E03719EB6921');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E037545317D1');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE dossier');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE vehicle');
    }
}
