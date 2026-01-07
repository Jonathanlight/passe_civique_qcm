<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106205933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE package (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, duration_in_months INT NOT NULL, stripe_price_id VARCHAR(255) DEFAULT NULL, stripe_product_id VARCHAR(255) DEFAULT NULL, state VARCHAR(50) NOT NULL, max_quizzes INT DEFAULT NULL, is_popular TINYINT(1) NOT NULL, features JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, package_id INT NOT NULL, status VARCHAR(50) NOT NULL, stripe_subscription_id VARCHAR(255) DEFAULT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, stripe_invoice_id VARCHAR(255) DEFAULT NULL, stripe_invoice_url VARCHAR(500) DEFAULT NULL, stripe_invoice_pdf VARCHAR(500) DEFAULT NULL, amount_paid NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, cancelled_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_A3C664D3A76ED395 (user_id), INDEX IDX_A3C664D3F44CABFF (package_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3F44CABFF FOREIGN KEY (package_id) REFERENCES package (id)');
        $this->addSql('ALTER TABLE quiz_answer RENAME INDEX idx_52d862ea853cd175 TO IDX_3799BA7C853CD175');
        $this->addSql('ALTER TABLE quiz_answer RENAME INDEX idx_52d862ea1e27f6bf TO IDX_3799BA7C1E27F6BF');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL, ADD email_verification_token VARCHAR(255) DEFAULT NULL, ADD email_verification_token_expires_at DATETIME DEFAULT NULL, ADD oauth_provider VARCHAR(50) DEFAULT NULL, ADD oauth_provider_id VARCHAR(255) DEFAULT NULL, ADD stripe_customer_id VARCHAR(255) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX idx_8d93d649b3c01b0a TO IDX_8D93D6496470145A');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3F44CABFF');
        $this->addSql('DROP TABLE package');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('ALTER TABLE quiz_answer RENAME INDEX idx_3799ba7c853cd175 TO IDX_52D862EA853CD175');
        $this->addSql('ALTER TABLE quiz_answer RENAME INDEX idx_3799ba7c1e27f6bf TO IDX_52D862EA1E27F6BF');
        $this->addSql('ALTER TABLE `user` DROP is_verified, DROP email_verification_token, DROP email_verification_token_expires_at, DROP oauth_provider, DROP oauth_provider_id, DROP stripe_customer_id, CHANGE password password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `user` RENAME INDEX idx_8d93d6496470145a TO IDX_8D93D649B3C01B0A');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }
}
