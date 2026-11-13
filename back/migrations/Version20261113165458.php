<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113165458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergene (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, note SMALLINT NOT NULL, description LONGTEXT NOT NULL, date_avis DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, utilisateur_id INT DEFAULT NULL, commande_id INT DEFAULT NULL, INDEX IDX_8F91ABF0FB88E14F (utilisateur_id), INDEX IDX_8F91ABF082EA2E54 (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, numero_commande VARCHAR(50) NOT NULL, date_commande DATE NOT NULL, date_prestation DATE NOT NULL, heure_livraison VARCHAR(50) NOT NULL, prix_menu DOUBLE PRECISION NOT NULL, nombre_personne INT NOT NULL, prix_livraison DOUBLE PRECISION NOT NULL, statut VARCHAR(50) NOT NULL, pret_materiel TINYINT NOT NULL, restition_materiel TINYINT NOT NULL, adresse_livraison VARCHAR(255) DEFAULT NULL, lieu_prestation VARCHAR(255) DEFAULT NULL, motif_annulation LONGTEXT DEFAULT NULL, mode_contact VARCHAR(20) DEFAULT NULL, utilisateur_id INT NOT NULL, menu_id INT NOT NULL, INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), INDEX IDX_6EEAA67DCCD7E912 (menu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE horaire (id INT AUTO_INCREMENT NOT NULL, jour VARCHAR(50) NOT NULL, heure_ouverture VARCHAR(50) NOT NULL, heure_fermeture VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(100) NOT NULL, nombre_personne_minimum INT NOT NULL, prix_par_personne DOUBLE PRECISION NOT NULL, description LONGTEXT NOT NULL, quantite_restante INT NOT NULL, regime_id INT DEFAULT NULL, theme_id INT DEFAULT NULL, INDEX IDX_7D053A9335E7D534 (regime_id), INDEX IDX_7D053A9359027487 (theme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menu_plat (menu_id INT NOT NULL, plat_id INT NOT NULL, INDEX IDX_E8775249CCD7E912 (menu_id), INDEX IDX_E8775249D73DB560 (plat_id), PRIMARY KEY (menu_id, plat_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plat (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(100) NOT NULL, type_plat VARCHAR(20) NOT NULL, photo VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plat_allergene (plat_id INT NOT NULL, allergene_id INT NOT NULL, INDEX IDX_6FA44BBFD73DB560 (plat_id), INDEX IDX_6FA44BBF4646AB2 (allergene_id), PRIMARY KEY (plat_id, allergene_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE regime (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, telephone VARCHAR(20) NOT NULL, adresse VARCHAR(150) NOT NULL, ville VARCHAR(50) NOT NULL, code_postal VARCHAR(10) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF082EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DCCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A9335E7D534 FOREIGN KEY (regime_id) REFERENCES regime (id)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A9359027487 FOREIGN KEY (theme_id) REFERENCES theme (id)');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT FK_6FA44BBFD73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT FK_6FA44BBF4646AB2 FOREIGN KEY (allergene_id) REFERENCES allergene (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0FB88E14F');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF082EA2E54');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DCCD7E912');
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A9335E7D534');
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A9359027487');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY FK_E8775249CCD7E912');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY FK_E8775249D73DB560');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY FK_6FA44BBFD73DB560');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY FK_6FA44BBF4646AB2');
        $this->addSql('DROP TABLE allergene');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE menu_plat');
        $this->addSql('DROP TABLE plat');
        $this->addSql('DROP TABLE plat_allergene');
        $this->addSql('DROP TABLE regime');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE utilisateur');
    }
}
