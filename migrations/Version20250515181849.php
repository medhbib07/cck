<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515181849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE etablissement DROP pays, CHANGE etype etype VARCHAR(50) NOT NULL, CHANGE adresse adresse VARCHAR(191) NOT NULL, CHANGE code_postal code_postal VARCHAR(10) DEFAULT NULL, CHANGE ville ville VARCHAR(100) NOT NULL, CHANGE date_fondation date_creation DATE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_717E22E3E7927C74 ON etudiant (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_717E22E3BB623174 ON etudiant (num_cin)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE etablissement ADD pays VARCHAR(100) DEFAULT NULL, CHANGE etype etype VARCHAR(191) NOT NULL, CHANGE adresse adresse VARCHAR(191) DEFAULT NULL, CHANGE code_postal code_postal VARCHAR(20) DEFAULT NULL, CHANGE ville ville VARCHAR(100) DEFAULT NULL, CHANGE date_creation date_fondation DATE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_717E22E3E7927C74 ON etudiant
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_717E22E3BB623174 ON etudiant
        SQL);
    }
}
