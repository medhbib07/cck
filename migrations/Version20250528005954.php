<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528005954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ministre (id INT NOT NULL, nom VARCHAR(191) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ministre ADD CONSTRAINT FK_9C9D597EBF396750 FOREIGN KEY (id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etablissement ADD adresse VARCHAR(191) NOT NULL, ADD code_postal VARCHAR(10) DEFAULT NULL, ADD ville VARCHAR(100) NOT NULL, ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, ADD telephone VARCHAR(20) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD date_creation DATE DEFAULT NULL, ADD capacite INT DEFAULT NULL, DROP localisation, CHANGE nom nom VARCHAR(191) NOT NULL, CHANGE etype etype VARCHAR(50) NOT NULL, CHANGE logo logo VARCHAR(191) DEFAULT NULL, CHANGE siteweb siteweb VARCHAR(191) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant ADD num_cin VARCHAR(50) NOT NULL, ADD section VARCHAR(100) NOT NULL, ADD score DOUBLE PRECISION DEFAULT NULL, ADD niveau VARCHAR(100) NOT NULL, ADD localisation VARCHAR(255) NOT NULL
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
            ALTER TABLE ministre DROP FOREIGN KEY FK_9C9D597EBF396750
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ministre
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etablissement ADD localisation VARCHAR(255) NOT NULL, DROP adresse, DROP code_postal, DROP ville, DROP latitude, DROP longitude, DROP telephone, DROP description, DROP date_creation, DROP capacite, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE etype etype VARCHAR(255) NOT NULL, CHANGE logo logo VARCHAR(255) DEFAULT NULL, CHANGE siteweb siteweb VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_717E22E3E7927C74 ON etudiant
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_717E22E3BB623174 ON etudiant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant DROP num_cin, DROP section, DROP score, DROP niveau, DROP localisation
        SQL);
    }
}
