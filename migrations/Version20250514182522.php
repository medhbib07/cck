<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514182522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
$this->addSql(<<<'SQL'
    ALTER TABLE etablissement
    ADD FOREIGN KEY (id) REFERENCES `user` (id) ON DELETE CASCADE
SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant ADD num_cin VARCHAR(50) NOT NULL, ADD section VARCHAR(100) NOT NULL, ADD score DOUBLE PRECISION DEFAULT NULL, ADD niveau VARCHAR(100) NOT NULL, ADD localisation VARCHAR(255) NOT NULL, CHANGE etablissement_id etablissement_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant ADD CONSTRAINT FK_717E22E3FF631228 FOREIGN KEY (etablissement_id) REFERENCES etablissement (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_717E22E3E7927C74 ON etudiant (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_717E22E3BB623174 ON etudiant (num_cin)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE universite ADD CONSTRAINT FK_B47BD9A3BF396750 FOREIGN KEY (id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE etablissement DROP FOREIGN KEY FK_20FD592CBF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant DROP FOREIGN KEY FK_717E22E3FF631228
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_717E22E3E7927C74 ON etudiant
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_717E22E3BB623174 ON etudiant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE etudiant DROP num_cin, DROP section, DROP score, DROP niveau, DROP localisation, CHANGE etablissement_id etablissement_id BIGINT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE universite DROP FOREIGN KEY FK_B47BD9A3BF396750
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` CHANGE email email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE type type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`
        SQL);
    }
}
