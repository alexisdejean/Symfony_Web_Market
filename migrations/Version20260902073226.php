<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902073226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_4C62E638A76ED395 ON contact (user_id)');
        $this->addSql('ALTER TABLE panier ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_24CC0DF2A76ED395 ON panier (user_id)');
        $this->addSql('ALTER TABLE panier_contenu ADD panier_id INT NOT NULL, ADD produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE panier_contenu ADD CONSTRAINT FK_7378C4BEF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id)');
        $this->addSql('ALTER TABLE panier_contenu ADD CONSTRAINT FK_7378C4BEF347EFB FOREIGN KEY (produit_id) REFERENCES produits (id)');
        $this->addSql('CREATE INDEX IDX_7378C4BEF77D927C ON panier_contenu (panier_id)');
        $this->addSql('CREATE INDEX IDX_7378C4BEF347EFB ON panier_contenu (produit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638A76ED395');
        $this->addSql('DROP INDEX IDX_4C62E638A76ED395 ON contact');
        $this->addSql('ALTER TABLE contact DROP user_id');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('DROP INDEX UNIQ_24CC0DF2A76ED395 ON panier');
        $this->addSql('ALTER TABLE panier DROP user_id');
        $this->addSql('ALTER TABLE panier_contenu DROP FOREIGN KEY FK_7378C4BEF77D927C');
        $this->addSql('ALTER TABLE panier_contenu DROP FOREIGN KEY FK_7378C4BEF347EFB');
        $this->addSql('DROP INDEX IDX_7378C4BEF77D927C ON panier_contenu');
        $this->addSql('DROP INDEX IDX_7378C4BEF347EFB ON panier_contenu');
        $this->addSql('ALTER TABLE panier_contenu DROP panier_id, DROP produit_id');
    }
}
