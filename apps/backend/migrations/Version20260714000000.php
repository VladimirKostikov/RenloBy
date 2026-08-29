<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Renlo schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');

        $this->addSql('CREATE TABLE cities (id SERIAL NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, PRIMARY KEY(id))');

        $this->addSql('CREATE TABLE districts (id SERIAL NOT NULL, city_id INT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_68E318DC8BAC62AF ON districts (city_id)');
        $this->addSql('ALTER TABLE districts ADD CONSTRAINT FK_68E318DC8BAC62AF FOREIGN KEY (city_id) REFERENCES cities (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE metro_stations (id SERIAL NOT NULL, city_id INT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, line_color VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4E8189C38BAC62AF ON metro_stations (city_id)');
        $this->addSql('ALTER TABLE metro_stations ADD CONSTRAINT FK_4E8189C38BAC62AF FOREIGN KEY (city_id) REFERENCES cities (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE listings (id SERIAL NOT NULL, user_id INT NOT NULL, city_id INT NOT NULL, district_id INT NOT NULL, metro_station_id INT DEFAULT NULL, deal_type VARCHAR(20) NOT NULL, listing_type VARCHAR(20) NOT NULL, price INT NOT NULL, price_per_sqm INT NOT NULL, rooms SMALLINT NOT NULL, area DOUBLE PRECISION NOT NULL, floor SMALLINT NOT NULL, total_floors SMALLINT NOT NULL, address VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, metro_minutes SMALLINT DEFAULT NULL, verified BOOLEAN NOT NULL, ai_good_price BOOLEAN NOT NULL, views INT NOT NULL, images JSON NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9A6FD5F6A76ED395 ON listings (user_id)');
        $this->addSql('CREATE INDEX IDX_9A6FD5F68BAC62AF ON listings (city_id)');
        $this->addSql('CREATE INDEX IDX_9A6FD5F6B08FA272 ON listings (district_id)');
        $this->addSql('CREATE INDEX IDX_9A6FD5F6F7D58AAA ON listings (metro_station_id)');
        $this->addSql('ALTER TABLE listings ADD CONSTRAINT FK_9A6FD5F6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE listings ADD CONSTRAINT FK_9A6FD5F68BAC62AF FOREIGN KEY (city_id) REFERENCES cities (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE listings ADD CONSTRAINT FK_9A6FD5F6B08FA272 FOREIGN KEY (district_id) REFERENCES districts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE listings ADD CONSTRAINT FK_9A6FD5F6F7D58AAA FOREIGN KEY (metro_station_id) REFERENCES metro_stations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE favorites (id SERIAL NOT NULL, user_id INT NOT NULL, listing_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E46960F5A76ED395 ON favorites (user_id)');
        $this->addSql('CREATE INDEX IDX_E46960F5D4619D1A ON favorites (listing_id)');
        $this->addSql('CREATE UNIQUE INDEX favorites_user_listing_unique ON favorites (user_id, listing_id)');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5D4619D1A FOREIGN KEY (listing_id) REFERENCES listings (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE comparisons (id SERIAL NOT NULL, user_id INT NOT NULL, listing_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AAAECBCDA76ED395 ON comparisons (user_id)');
        $this->addSql('CREATE INDEX IDX_AAAECBCDD4619D1A ON comparisons (listing_id)');
        $this->addSql('CREATE UNIQUE INDEX comparisons_user_listing_unique ON comparisons (user_id, listing_id)');
        $this->addSql('ALTER TABLE comparisons ADD CONSTRAINT FK_AAAECBCDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comparisons ADD CONSTRAINT FK_AAAECBCDD4619D1A FOREIGN KEY (listing_id) REFERENCES listings (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE saved_searches (id SERIAL NOT NULL, user_id INT NOT NULL, name VARCHAR(150) NOT NULL, filters JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4F966CE9A76ED395 ON saved_searches (user_id)');
        $this->addSql('ALTER TABLE saved_searches ADD CONSTRAINT FK_4F966CE9A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE saved_searches DROP CONSTRAINT FK_4F966CE9A76ED395');
        $this->addSql('DROP TABLE saved_searches');
        $this->addSql('ALTER TABLE comparisons DROP CONSTRAINT FK_AAAECBCDD4619D1A');
        $this->addSql('ALTER TABLE comparisons DROP CONSTRAINT FK_AAAECBCDA76ED395');
        $this->addSql('DROP TABLE comparisons');
        $this->addSql('ALTER TABLE favorites DROP CONSTRAINT FK_E46960F5D4619D1A');
        $this->addSql('ALTER TABLE favorites DROP CONSTRAINT FK_E46960F5A76ED395');
        $this->addSql('DROP TABLE favorites');
        $this->addSql('ALTER TABLE listings DROP CONSTRAINT FK_9A6FD5F6F7D58AAA');
        $this->addSql('ALTER TABLE listings DROP CONSTRAINT FK_9A6FD5F6B08FA272');
        $this->addSql('ALTER TABLE listings DROP CONSTRAINT FK_9A6FD5F68BAC62AF');
        $this->addSql('ALTER TABLE listings DROP CONSTRAINT FK_9A6FD5F6A76ED395');
        $this->addSql('DROP TABLE listings');
        $this->addSql('ALTER TABLE metro_stations DROP CONSTRAINT FK_4E8189C38BAC62AF');
        $this->addSql('DROP TABLE metro_stations');
        $this->addSql('ALTER TABLE districts DROP CONSTRAINT FK_68E318DC8BAC62AF');
        $this->addSql('DROP TABLE districts');
        $this->addSql('DROP TABLE cities');
        $this->addSql('DROP TABLE users');
    }
}
