# Environments with docker compose

## Local development environment

The `docker-compose.yml` provides the base stack

The `docker-compose.dev.yml` extends the base stack a local development environment.

### Usage (TL;DR)

Run all commands from the `docker` directory.

#### Start

Start the stack: `docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d`

#### Stop

Stop the stack: `docker compose -f docker-compose.yml -f docker-compose.dev.yml down`

#### Reset database

Destroy the stack with volume wipe `docker compose -f docker-compose.yml -f docker-compose.dev.yml -v down`

and then start the stack again: `docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d`

#### Migration

`docker compose exec -ti app php artisan migrate -n`

#### Other artisan commands

`docker compose exec -ti app php artisan help`

#### Reindex

`docker compose exec -ti sphinx indexer --all --rotate`

#### Import

#### Open shell terminal in the app

`docker compose exec -ti app bash`

### The details of the services

#### app

It build a webserver that based on the official `php` docker image with apache and 8.2 php version.
You can find the details in the `Dockerfile`.

It mounts the whole code under the `/var/www/html`, so what you modified that appear in the local server.

The app is reachable at the `http://localhost:8080` url.

#### database

It's a mariadb instance. The database folder in a named volume: `db-data`. You can zap it with `docker compose down -v` command. Notice the `-v` parameter.

The database seed at the first run with the dump in the `tmp` folder. You can put there another dump, sql scripts as you can read it the [mariadb docker image documentation](https://hub.docker.com/_/mariadb) (Initializing the database contents section)

The hostname of the service in the docker default network is `database`, be careful to use it in the .env files.

The sql port expose to localhost, so you can use any mysql client in the localhost at 3306 port.

#### sphinx

Sphinxsearch indexer. The config files are in `deploy` folder, there are `__ENV_VAR__` placehoders in them.
The `docker/sphinx/start.sh` changes the placeholders to the environment variable's vaules, initializes the index files.
In `dev` environment the folder of data files mounted into the container in `production` environment this folder is persisted to a named volume to avoid loss between container restarts.

#### mailpit

A fancy SMTP mail catcher with mail format analyser, and with API for easy testing.

The smtp service is at "localhost:1025" and in the docker network: "mailpit:1025".

The web ui at [http://localhost:8025](http://localhost:8025).

#### composer

It installs the php dependencies with the composer.phar that's in the repo root folder.
Changing the composer.json you should run the service again to install/update the php dependencies.

It runs and exits.

#### node

Install the nodejs dependencies what's in the package-lock.json.
You shoud run it when change the nodejs dependencies.

It runs and exit.

#### gulp

Compile the assets to public build css js folders. It uses the nodejs and the installed composer artifacts.

#### The starting order

With the `depends_on` keywords we can controll the order of the starting process.

**Independent services**

1. The **mailpit** start without any dependency.
2. The **composer** service makes the php composer install.
3. Start the **database** with the initializaton.
   When the database finish the init process they status will be healthy.
4. The **composer** install the php dependecies. The vendor folder created in the project folder (the .gitignore responsible to not appear in the version control)
5. The **node** service install the nodejs dependecies. The node_modules folder created in the project folder (the .gitignore responsible to not appear in the version control)

**Dependent services**

1. The **gulp** compiler runs after the node and composer services finished the process and exits without any errors.
2. The **migrator** runs when the database is healthy and the composer exits without any errors.
3. The **sphinx** started when the database is healthy and the migrator exits without any errors.
4. The **app** started when the database is healthy and the migrator exits without any errors.

### Exposed ports

| service        | protocol | port/url              |
| --             | --       | --                    |
| app            | http     | http://localhost:8080 |
| database       | mysql    | 3306                  |
| mailpit smtp   | smtp     | 1025                  |
| mailpit web ui | http     | http://localhost:8025 |

### The built images, Dockerfile explanation

**ARG Statements**: Define the versions for Alpine, Node, and Composer.
**Stage 1 (Node setup)**: Use the specified Node version to create a base image.
**Stage 2 (Gulp setup)**: Use the specified Alpine version to create a base image, copy Node binaries, and install necessary packages.
**Stage 3 (Composer installer setup)**: Use the specified Composer version to create a base image.
**Stage 4 (PHP setup)**: Use the PHP 8.2 Apache image, copy Composer from the previous stage, and install PHP extensions and dependencies.
**Stage 5 (Gulp builder)**: Use the Gulp setup from Stage 2, copy the PHP application, and run Gulp tasks.
**Stage 6 (Production setup)**: Use the PHP setup from Stage 4, install additional PHP extensions, and copy built assets from the Gulp builder stage.
**Stage 7 (Development setup)**: Use the production setup from Stage 6, install development tools and Xdebug, and configure PHP for development.

This structure allows for efficient multi-stage builds, reducing the final image size and ensuring that each stage has the necessary dependencies and configurations.
