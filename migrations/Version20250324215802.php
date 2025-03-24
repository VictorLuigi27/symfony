<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250324215802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_game DROP FOREIGN KEY FK_A8B04BCB12469DE2');
        $this->addSql('ALTER TABLE category_game DROP FOREIGN KEY FK_A8B04BCBE48FD905');
        $this->addSql('DROP TABLE category_game');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category_game (category_id INT NOT NULL, game_id INT NOT NULL, INDEX IDX_A8B04BCB12469DE2 (category_id), INDEX IDX_A8B04BCBE48FD905 (game_id), PRIMARY KEY(category_id, game_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE category_game ADD CONSTRAINT FK_A8B04BCB12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_game ADD CONSTRAINT FK_A8B04BCBE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
