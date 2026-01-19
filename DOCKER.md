# Docker Setup Instructions

## Permission Issue Fix

If you encounter permission errors when running Docker commands, you have two options:

### Option 1: Use sudo (Temporary)
```bash
sudo docker-compose up -d
sudo docker-compose ps
sudo docker-compose logs -f
```

### Option 2: Add User to Docker Group (Permanent)
```bash
# Add your user to the docker group
sudo usermod -aG docker $USER

# Log out and log back in, OR run:
newgrp docker

# Now you can run docker commands without sudo
docker-compose up -d
```

## Quick Start Commands

```bash
# Start all containers
sudo docker-compose up -d

# Check container status
sudo docker-compose ps

# View logs
sudo docker-compose logs -f app
sudo docker-compose logs -f nginx

# Stop containers
sudo docker-compose down

# Rebuild containers
sudo docker-compose up -d --build

# Access container shell
sudo docker-compose exec app bash

# Run Symfony console commands
sudo docker-compose exec app php bin/console list
```

## Accessing Services

- **Application**: http://localhost:8080
- **PhpMyAdmin**: http://localhost:8081
  - Server: `db`
  - Username: `root`
  - Password: `root_password`

## Database Connection

Inside containers, use:
- Host: `db`
- Port: `3306`
- Database: `lbook_db`
- User: `lbook_user`
- Password: `lbook_password`

From host machine, use:
- Host: `localhost`
- Port: `3306`

## Useful Docker Commands

```bash
# View running containers
sudo docker ps

# View all containers (including stopped)
sudo docker ps -a

# Remove all stopped containers
sudo docker container prune

# View images
sudo docker images

# Remove unused images
sudo docker image prune

# View volumes
sudo docker volume ls

# Remove unused volumes
sudo docker volume prune
```

## Troubleshooting

### Containers not starting
```bash
# Check logs
sudo docker-compose logs

# Rebuild
sudo docker-compose down
sudo docker-compose up -d --build
```

### Port already in use
```bash
# Find what's using the port
sudo lsof -i :8080
sudo lsof -i :3306

# Kill the process or change port in docker-compose.yml
```

### Database connection issues
```bash
# Wait for MySQL to fully start (can take 30-60 seconds)
sudo docker-compose logs db

# Test connection
sudo docker-compose exec db mysql -u root -proot_password
```
