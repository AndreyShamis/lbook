<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180613073232 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE lbook_user_settings (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, cycle_show_test_id_show TINYINT(1) DEFAULT \'1\' NOT NULL, cycle_show_test_time_start_show TINYINT(1) DEFAULT \'1\' NOT NULL, cycle_show_test_time_end_show TINYINT(1) DEFAULT \'1\' NOT NULL, cycle_show_test_time_ratio_show TINYINT(1) DEFAULT \'1\' NOT NULL, cycle_show_test_time_start_format VARCHAR(50) DEFAULT \'H:i:s\' NOT NULL, cycle_show_test_time_end_format VARCHAR(50) DEFAULT \'H:i:s\' NOT NULL, UNIQUE INDEX UNIQ_5677F0B0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lbook_user_settings ADD CONSTRAINT FK_5677F0B0A76ED395 FOREIGN KEY (user_id) REFERENCES lbook_users (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP TABLE lbook_user_settings');
    }
}
