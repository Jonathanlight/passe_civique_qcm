<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260119110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD password_reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD password_reset_token_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN password_reset_token');
        $this->addSql('ALTER TABLE `user` DROP COLUMN password_reset_token_expires_at');
    }
}
