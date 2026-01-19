<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260119100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add difficulty field to quiz_configuration and remove is_free_access';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_configuration ADD difficulty VARCHAR(20) NOT NULL DEFAULT \'medium\'');
        $this->addSql('ALTER TABLE quiz_configuration DROP COLUMN is_free_access');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_configuration ADD is_free_access BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE quiz_configuration DROP COLUMN difficulty');
    }
}
