<?php

declare(strict_types=1);

namespace KaLehmann\UnlockedServer\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220410222214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database setup';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE clients ' .
            '(handle VARCHAR(255) NOT NULL,' .
            ' user_handle VARCHAR(255) DEFAULT NULL,' .
            ' deleted BOOLEAN NOT NULL,' .
            ' description VARCHAR(255) NOT NULL,' .
            ' secret VARCHAR(255) NOT NULL,' .
            ' PRIMARY KEY(handle))',
        );
        $this->addSql(
            'CREATE INDEX IDX_C82E74F4D23BE4 ON clients (user_handle)',
        );
        $this->addSql(
            'CREATE TABLE keys ' .
            '(deleted BOOLEAN NOT NULL,' .
            ' handle VARCHAR(255) NOT NULL,' .
            ' user_handle VARCHAR(255) DEFAULT NULL,' .
            ' description VARCHAR(255) NOT NULL,' .
            ' "key" VARCHAR(255) NOT NULL,' .
            ' PRIMARY KEY(handle))',
        );
        $this->addSql(
            'CREATE INDEX IDX_B48E44ECF4D23BE4 ON keys (user_handle)',
        );
        $this->addSql(
            'CREATE TABLE requests ' .
            '(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,' .
            ' client_handle VARCHAR(255) DEFAULT NULL,' .
            ' key_handle VARCHAR(255) DEFAULT NULL,' .
            ' user_handle VARCHAR(255) DEFAULT NULL,' .
            ' created INTEGER NOT NULL,' .
            ' expires INTEGER NOT NULL,' .
            ' fulfilled INTEGER DEFAULT NULL,' .
            ' processed INTEGER DEFAULT NULL,' .
            ' state VARCHAR(255) NOT NULL)',
        );
        $this->addSql(
            'CREATE INDEX IDX_7B85D651C4266B6A ON requests (client_handle)',
        );
        $this->addSql(
            'CREATE INDEX IDX_7B85D6519AB9C185 ON requests (key_handle)',
        );
        $this->addSql(
            'CREATE INDEX IDX_7B85D651F4D23BE4 ON requests (user_handle)',
        );
        $this->addSql(
            'CREATE TABLE tokens ' .
            '(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,' .
            ' user_handle VARCHAR(255) DEFAULT NULL,' .
            ' description VARCHAR(255) NOT NULL,' .
            ' expires INTEGER NOT NULL,' .
            ' revoked BOOLEAN NOT NULL,' .
            ' token VARCHAR(255) NOT NULL)',
        );
        $this->addSql(
            'CREATE INDEX IDX_AA5A118EF4D23BE4 ON tokens (user_handle)',
        );
        $this->addSql(
            'CREATE TABLE users ' .
            '(handle VARCHAR(255) NOT NULL,' .
            ' email VARCHAR(255) NOT NULL,' .
            ' mobile VARCHAR(255) DEFAULT NULL,' .
            ' password VARCHAR(255) NOT NULL,' .
            ' PRIMARY KEY(handle))',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_1483A5E93C7323E0 ON users (mobile)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE keys');
        $this->addSql('DROP TABLE requests');
        $this->addSql('DROP TABLE tokens');
        $this->addSql('DROP TABLE users');
    }
}
