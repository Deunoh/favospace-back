<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241024132526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mark ADD space_id INT NOT NULL');
        $this->addSql('ALTER TABLE mark ADD CONSTRAINT FK_6674F27123575340 FOREIGN KEY (space_id) REFERENCES space (id)');
        $this->addSql('CREATE INDEX IDX_6674F27123575340 ON mark (space_id)');
        $this->addSql('ALTER TABLE space ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE space ADD CONSTRAINT FK_2972C13AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2972C13AA76ED395 ON space (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mark DROP FOREIGN KEY FK_6674F27123575340');
        $this->addSql('DROP INDEX IDX_6674F27123575340 ON mark');
        $this->addSql('ALTER TABLE mark DROP space_id');
        $this->addSql('ALTER TABLE space DROP FOREIGN KEY FK_2972C13AA76ED395');
        $this->addSql('DROP INDEX IDX_2972C13AA76ED395 ON space');
        $this->addSql('ALTER TABLE space DROP user_id');
    }
}
