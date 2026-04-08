<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212033921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE catering_product (catering_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_310CECF9E42F97B9 (catering_id), INDEX IDX_310CECF94584665A (product_id), PRIMARY KEY (catering_id, product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE catering_product ADD CONSTRAINT FK_310CECF9E42F97B9 FOREIGN KEY (catering_id) REFERENCES catering (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE catering_product ADD CONSTRAINT FK_310CECF94584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE catering ADD event_date DATETIME NOT NULL, ADD status VARCHAR(50) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE created_by_id created_by_id INT DEFAULT NULL, CHANGE slots number_of_guests INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catering_product DROP FOREIGN KEY FK_310CECF9E42F97B9');
        $this->addSql('ALTER TABLE catering_product DROP FOREIGN KEY FK_310CECF94584665A');
        $this->addSql('DROP TABLE catering_product');
        $this->addSql('ALTER TABLE catering DROP event_date, DROP status, CHANGE name name VARCHAR(180) NOT NULL, CHANGE created_by_id created_by_id INT NOT NULL, CHANGE number_of_guests slots INT NOT NULL');
    }
}
