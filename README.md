# La Fourche Backend Developer test

This is a boilerplate for La Fourche Backend Developer test.
You were sent what to do from here by email.

This repository is a fork of [Symfony Docker](https://github.com/dunglas/symfony-docker).
It setups a Symfony in docker containers.

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker-compose build --pull --no-cache` to build fresh images
3. Run `docker-compose up` (the logs will be displayed in the current shell)
4. Open `https://localhost` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
4. Open `https://localhost:8080` to query database, DSN in .env file


## Setup vendors and database

```
# run cli in the php container
docker-compose exec php /bin/sh

# add vendors
composer install

# update the database schema 
bin/console doctrine:schema:update -f

# to load fixtures data
bin/console doctrine:fixtures:load
```
