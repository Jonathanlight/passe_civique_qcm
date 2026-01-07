<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for Passe Civique QCM application';
    }

    public function up(Schema $schema): void
    {
        // GamificationLevel table
        $this->addSql('CREATE TABLE gamification_level (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            emoji VARCHAR(20) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            min_quizzes_passed INT NOT NULL,
            min_average_score INT NOT NULL,
            display_order INT NOT NULL,
            badge_color VARCHAR(20) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // User table
        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT NOT NULL,
            gamification_level_id INT DEFAULT NULL,
            email VARCHAR(180) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            last_login_at DATETIME DEFAULT NULL,
            is_active TINYINT(1) NOT NULL,
            registration_ip VARCHAR(45) DEFAULT NULL,
            last_login_ip VARCHAR(45) DEFAULT NULL,
            total_quizzes_passed INT NOT NULL,
            average_score NUMERIC(5, 2) NOT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            INDEX IDX_8D93D649B3C01B0A (gamification_level_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Theme table
        $this->addSql('CREATE TABLE theme (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(150) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            color VARCHAR(20) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL,
            display_order INT NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Question table
        $this->addSql('CREATE TABLE question (
            id INT AUTO_INCREMENT NOT NULL,
            theme_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            is_multiple_choice TINYINT(1) NOT NULL,
            explanation LONGTEXT DEFAULT NULL,
            difficulty VARCHAR(20) NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_B6F7494E59027487 (theme_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Answer table
        $this->addSql('CREATE TABLE answer (
            id INT AUTO_INCREMENT NOT NULL,
            question_id INT NOT NULL,
            content VARCHAR(500) NOT NULL,
            is_correct TINYINT(1) NOT NULL,
            display_order INT NOT NULL,
            INDEX IDX_DADD4A251E27F6BF (question_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // QuizConfiguration table
        $this->addSql('CREATE TABLE quiz_configuration (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(150) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            questions_count INT NOT NULL,
            minimum_correct_answers INT NOT NULL,
            minimum_score_percent NUMERIC(5, 2) NOT NULL,
            time_limit_minutes INT DEFAULT NULL,
            shuffle_questions TINYINT(1) NOT NULL,
            shuffle_answers TINYINT(1) NOT NULL,
            show_explanation_after_answer TINYINT(1) NOT NULL,
            show_results_at_end TINYINT(1) NOT NULL,
            allow_retake TINYINT(1) NOT NULL,
            is_default TINYINT(1) NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Quiz table
        $this->addSql('CREATE TABLE quiz (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            configuration_id INT NOT NULL,
            started_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            total_questions INT NOT NULL,
            correct_answers INT NOT NULL,
            score_percent NUMERIC(5, 2) NOT NULL,
            is_passed TINYINT(1) NOT NULL,
            status VARCHAR(20) NOT NULL,
            current_question_index INT NOT NULL,
            INDEX IDX_A412FA92A76ED395 (user_id),
            INDEX IDX_A412FA9273F32DD8 (configuration_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // QuizAnswer table
        $this->addSql('CREATE TABLE quiz_answer (
            id INT AUTO_INCREMENT NOT NULL,
            quiz_id INT NOT NULL,
            question_id INT NOT NULL,
            selected_answer_ids JSON NOT NULL,
            is_correct TINYINT(1) NOT NULL,
            answered_at DATETIME NOT NULL,
            time_spent_seconds INT DEFAULT NULL,
            INDEX IDX_52D862EA853CD175 (quiz_id),
            INDEX IDX_52D862EA1E27F6BF (question_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Messenger messages table
        $this->addSql('CREATE TABLE messenger_messages (
            id BIGINT AUTO_INCREMENT NOT NULL,
            body LONGTEXT NOT NULL,
            headers LONGTEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_75EA56E0FB7336F0 (queue_name),
            INDEX IDX_75EA56E0E3BD61CE (available_at),
            INDEX IDX_75EA56E016BA31DB (delivered_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649B3C01B0A FOREIGN KEY (gamification_level_id) REFERENCES gamification_level (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E59027487 FOREIGN KEY (theme_id) REFERENCES theme (id)');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A251E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA9273F32DD8 FOREIGN KEY (configuration_id) REFERENCES quiz_configuration (id)');
        $this->addSql('ALTER TABLE quiz_answer ADD CONSTRAINT FK_52D862EA853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz_answer ADD CONSTRAINT FK_52D862EA1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_answer DROP FOREIGN KEY FK_52D862EA853CD175');
        $this->addSql('ALTER TABLE quiz_answer DROP FOREIGN KEY FK_52D862EA1E27F6BF');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92A76ED395');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA9273F32DD8');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A251E27F6BF');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E59027487');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649B3C01B0A');

        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE quiz_answer');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE quiz_configuration');
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE gamification_level');
    }
}