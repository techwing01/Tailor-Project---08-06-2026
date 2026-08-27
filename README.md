# TailorMate

TailorMate is a PHP/MySQL web application for managing tailoring business operations, including customers, measurements, orders, payments, and invoices.

## Repository status

This repository contains the sanitized application baseline intended for development, code review, CI validation, and controlled production deployment.

Production secrets, customer/order data, database dumps, and local backups are intentionally excluded from source control.

## Technology stack

- PHP 8.1
- Apache 2.4
- MariaDB 10.11
- Bootstrap 5.3
- Font Awesome 6.4
- Chart.js
- Docker Compose
- Tailscale Funnel for the current public endpoint
- Resend for transactional email

## Local configuration

1. Copy `.env.example` to `.env`.
2. Populate database credentials, the MariaDB root password, the administrator bootstrap password, and the Resend API key.
3. Keep `.env` local and never commit it.

## Docker

The application is designed to run with Docker Compose. The database uses a persistent named volume so application deployments do not replace production data.

Typical commands:

```bash
docker compose config
docker compose up -d
docker compose ps
```

## Database

`database/schema.sql` contains the schema-only baseline with no production records.

Production database backups must remain outside the repository and should be managed through the deployment/backup process.

## CI/CD direction

The intended delivery model is:

```text
feature branch -> CI validation -> main -> controlled production deployment
```

CI should validate PHP syntax, Docker configuration/buildability, and repository security. Production secrets must be injected by the deployment environment rather than stored in Git.

## Security notes

- `.env` is excluded from Git.
- Production/customer data is excluded from Git.
- The repository must not contain API keys or database passwords.
- The current production environment should be backed up before application deployments.

## Current production endpoint

The current environment is exposed through Tailscale Funnel. The hostname is environment-specific and is intentionally not hard-coded into the application source.
