<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: user, admin, events, reservations, webauthn_credential, refresh_tokens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "user" (
            id UUID NOT NULL,
            username VARCHAR(180) NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_USERNAME ON "user" (username)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE admin (
            id SERIAL NOT NULL,
            username VARCHAR(180) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ADMIN_USERNAME ON admin (username)');

        $this->addSql('CREATE TABLE events (
            id SERIAL NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            location VARCHAR(255) NOT NULL,
            seats INT NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN events.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN events.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN events.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE reservations (
            id SERIAL NOT NULL,
            event_id INT NOT NULL,
            user_id UUID DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'confirmed\',
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_RES_EVENT ON reservations (event_id)');
        $this->addSql('CREATE INDEX IDX_RES_USER ON reservations (user_id)');
        $this->addSql('COMMENT ON COLUMN reservations.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_RES_EVENT FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_RES_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE webauthn_credential (
            id UUID NOT NULL,
            user_id UUID NOT NULL,
            credential_data TEXT NOT NULL,
            name VARCHAR(255) NOT NULL DEFAULT \'Ma cle\',
            credential_id BYTEA NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            last_used_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN webauthn_credential.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN webauthn_credential.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN webauthn_credential.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN webauthn_credential.last_used_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE webauthn_credential ADD CONSTRAINT FK_WA_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE refresh_tokens (
            id SERIAL NOT NULL,
            refresh_token VARCHAR(128) NOT NULL,
            username VARCHAR(255) NOT NULL,
            valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RT_TOKEN ON refresh_tokens (refresh_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE webauthn_credential');
        $this->addSql('DROP TABLE reservations');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE admin');
        $this->addSql('DROP TABLE "user"');
    }
}
