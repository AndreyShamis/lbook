# LogBook - Symfony 7.2 Application

Modern test management and logging application built with Symfony 7.2 and Docker.

## Legacy Version

The old version of this project is available in the `legacy` branch.

## Requirements

- Docker
- Docker Compose

## Installation

1. Clone the repository:
```bash
git clone https://github.com/AndreyShamis/lbook.git
cd lbook
```

2. Start Docker containers:
```bash
docker-compose up -d
```

3. Install dependencies (if needed):
```bash
docker-compose exec app composer install
```

4. Create database schema:
```bash
docker-compose exec app php bin/console doctrine:database:create
docker-compose exec app php bin/console doctrine:migrations:migrate
```

## Access

- **Application**: http://localhost:8080
- **PhpMyAdmin**: http://localhost:8081

## Docker Services

- **app** - PHP 8.3 FPM
- **nginx** - Nginx web server
- **db** - MySQL 8.0 database
- **phpmyadmin** - Database management interface

## Configuration

Edit `.env.local` for local environment configuration.

## Development

```bash
# View logs
docker-compose logs -f app

# Access container shell
docker-compose exec app bash

# Run Symfony commands
docker-compose exec app php bin/console [command]
```

## Stopping

```bash
docker-compose down
```

To remove volumes as well:
```bash
docker-compose down -v
```

## License

See LICENSE file for details.
