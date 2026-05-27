<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer verification and OAuth fields to users.';
    }

    public function up(Schema $schema): void
    {
        $userTable = $schema->getTable('user');

        if (!$userTable->hasColumn('is_verified')) {
            $this->addSql('ALTER TABLE `user` ADD is_verified TINYINT(1) NOT NULL DEFAULT 0');
        }

        if (!$userTable->hasColumn('google_id')) {
            $this->addSql('ALTER TABLE `user` ADD google_id VARCHAR(255) DEFAULT NULL');
        }

        if (!$userTable->hasColumn('verification_token')) {
            $this->addSql('ALTER TABLE `user` ADD verification_token VARCHAR(255) DEFAULT NULL');
        }

        if (!$userTable->hasColumn('verified_at')) {
            $this->addSql('ALTER TABLE `user` ADD verified_at DATETIME DEFAULT NULL');
        }

        if (!$userTable->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE `user` ADD updated_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $userTable = $schema->getTable('user');

        if ($userTable->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE `user` DROP updated_at');
        }

        if ($userTable->hasColumn('verified_at')) {
            $this->addSql('ALTER TABLE `user` DROP verified_at');
        }

        if ($userTable->hasColumn('verification_token')) {
            $this->addSql('ALTER TABLE `user` DROP verification_token');
        }

        if ($userTable->hasColumn('google_id')) {
            $this->addSql('ALTER TABLE `user` DROP google_id');
        }

        if ($userTable->hasColumn('is_verified')) {
            $this->addSql('ALTER TABLE `user` DROP is_verified');
        }
    }
}
