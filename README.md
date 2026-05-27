# Seafoodie

Seafoodie is a Symfony web admin panel plus JSON API for a seafood products and catering booking system. The web dashboard is used by staff/admin users, while the `/api/*` routes are ready for a customer mobile app.

## Rubric Coverage

- Customer API: customer registration/login, profile, products, and catering booking endpoints.
- Authentication/security: JWT login with protected API routes, hashed passwords, CORS config, and server-side validation.
- RBAC: customers use API booking/profile routes; staff/admin users manage products/bookings in the web dashboard.
- Synchronization: mobile API and web dashboard read/write the same MySQL database, so booking changes appear in the dashboard after refresh.
- Deployment: Docker Compose runs MySQL, PHP-FPM, Nginx, and phpMyAdmin.

## Run Locally

```bash
docker compose up -d
docker compose run --rm composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

Web app: `http://localhost:8001`

phpMyAdmin: `http://localhost:8006`

Demo accounts from fixtures:

- Admin: `admin@seafoodie.com` / `admin123`
- Staff: `staff@seafoodie.com` / `staff123`

## API Authentication

All protected routes require:

```http
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

### Register Customer

`POST /api/register`

```json
{
  "name": "Customer User",
  "email": "customer@example.com",
  "password": "secret123"
}
```

### Login

`POST /api/login`

```json
{
  "email": "customer@example.com",
  "password": "secret123"
}
```

The response includes a JWT token. Use it as the bearer token for the next API calls.

## Customer API Routes

### Get Profile

`GET /api/profile`

### Update Profile

`PATCH /api/profile`

```json
{
  "name": "Updated Customer",
  "email": "updated@example.com"
}
```

### List Products

`GET /api/products`

### Show Product

`GET /api/products/{id}`

### List My Bookings

`GET /api/bookings`

Customers see their own bookings. Staff/admin users can see all bookings.

### Create Booking

`POST /api/bookings`

```json
{
  "name": "Birthday Seafood Package",
  "description": "Lunch catering with grilled seafood",
  "eventDate": "2026-06-15 12:00:00",
  "numberOfGuests": 30,
  "price": 12000,
  "productIds": [1, 2]
}
```

### Show Booking

`GET /api/bookings/{id}`

### Update Booking

`PATCH /api/bookings/{id}`

```json
{
  "numberOfGuests": 40,
  "status": "Pending"
}
```

### Cancel Booking

`DELETE /api/bookings/{id}`

This marks the booking as `Cancelled` instead of physically deleting it, which is better for demos, reports, and dashboard synchronization.

## Standard API Response

Success:

```json
{
  "success": true,
  "message": "Booking created successfully",
  "data": {}
}
```

Validation error:

```json
{
  "success": false,
  "message": "Please correct the highlighted fields",
  "errors": {
    "name": "Booking name is required."
  }
}
```

## Presentation Flow

1. Start Docker and open the web dashboard.
2. Login as staff/admin and show products/bookings in the dashboard.
3. Register or login a customer through `/api/register` and `/api/login`.
4. Use the token to create a booking with `/api/bookings`.
5. Refresh the web dashboard or database table to show that the mobile/API change synchronized.
6. Try accessing another customer's booking to demonstrate protected access.
