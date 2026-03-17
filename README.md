# Laravel Dockerized Starter

A minimal Laravel environment fully containerized with Docker, ready for development.

## Requirements

- Docker
- cURL

## Quick Start

### Create project directory

First, create a directory for your new project and move into it:

```bash
mkdir NEW-PROJECT-NAME && cd NEW-PROJECT-NAME
```

### Download and run the bootstrapper:

**Option 1**. Using `cURL`:

```bash
curl -L -o setup.sh https://raw.githubusercontent.com/RonasIT/laravel-project-create/refs/heads/main/setup.sh && chmod +x setup.sh && ./setup.sh
```

The `setup.sh` script is a bootstrapper: when you run it, it will automatically download the additional project files
(`init-project.sh`, `docker-compose.yml`, `Dockerfile`, and `docker/entrypoint.sh`) into your project directory. You do not
need to download these files manually.

**Option 2**. Using `Git`:

```bash
git clone git@github.com:RonasIT/laravel-project-create.git NEW-PROJECT-NAME && cd NEW-PROJECT-NAME && ./setup.sh
```

Clone the repository and run the bootstrapper script.
The setup will execute automatically, preparing your Laravel project for development.

**Laravel project is ready for development!**
