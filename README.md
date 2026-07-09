# Odeimin.ca (Local Docker Setup)

Run the site locally with Docker Desktop (no local PHP install required).

## Requirements

- Docker Desktop
- Docker Compose v2 (`docker compose`)

## Quick Start

1. Ensure your app env file exists:

   - Copy `.env.example` to `.env`
   - Set either:
     - `ADMIN_PASSWORD_HASH` (recommended), or
     - `UPLOAD_PASSWORD` (legacy fallback)

2. Start the site:

   ```bash
   docker compose up -d
   ```

3. Open:

   - Gallery: http://localhost:8080/
   - About: http://localhost:8080/about
   - Contact: http://localhost:8080/contact
   - Admin: http://localhost:8080/upload

## Useful Commands

```bash
docker compose ps
docker compose logs -f web
docker compose down
```

## Notes

- Uploaded files are stored in `images/available` and `images/unavailable` in your project folder.
- The container auto-creates those folders on startup if missing.
- If port `8080` is busy, change `8080:80` in `docker-compose.yml`.
