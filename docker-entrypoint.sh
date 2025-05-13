#!/bin/sh
set -e

# Create .env.local with environment variables if they exist
touch .env.local

# Add DATABASE_URL
if [ ! -z "$DATABASE_URL" ]; then
  echo "DATABASE_URL=$DATABASE_URL" >> .env.local
fi

# Add APP_ENV
if [ ! -z "$APP_ENV" ]; then
  echo "APP_ENV=$APP_ENV" >> .env.local
fi

# Add APP_SECRET
if [ ! -z "$APP_SECRET" ]; then
  echo "APP_SECRET=$APP_SECRET" >> .env.local
fi

# Add STRIPE keys
if [ ! -z "$STRIPE_PUBLIC_KEY" ]; then
  echo "STRIPE_PUBLIC_KEY=$STRIPE_PUBLIC_KEY" >> .env.local
fi

if [ ! -z "$STRIPE_SECRET_KEY" ]; then
  echo "STRIPE_SECRET_KEY=$STRIPE_SECRET_KEY" >> .env.local
fi

if [ ! -z "$STRIPE_ALT_SECRET_KEY" ]; then
  echo "STRIPE_ALT_SECRET_KEY=$STRIPE_ALT_SECRET_KEY" >> .env.local
fi

# Add SMTP settings
if [ ! -z "$SMTP_HOST" ]; then
  echo "SMTP_HOST=$SMTP_HOST" >> .env.local
fi

if [ ! -z "$SMTP_PORT" ]; then
  echo "SMTP_PORT=$SMTP_PORT" >> .env.local
fi

if [ ! -z "$SMTP_USERNAME" ]; then
  echo "SMTP_USERNAME=$SMTP_USERNAME" >> .env.local
fi

if [ ! -z "$EMAIL_PASSWORD" ]; then
  echo "EMAIL_PASSWORD=$EMAIL_PASSWORD" >> .env.local
fi

# Add API keys
if [ ! -z "$GEMINI_API_KEY" ]; then
  echo "GEMINI_API_KEY=$GEMINI_API_KEY" >> .env.local
fi

if [ ! -z "$GEMINI_ALT_API_KEY" ]; then
  echo "GEMINI_ALT_API_KEY=$GEMINI_ALT_API_KEY" >> .env.local
fi

if [ ! -z "$FACE_PLUS_PLUS_API_KEY" ]; then
  echo "FACE_PLUS_PLUS_API_KEY=$FACE_PLUS_PLUS_API_KEY" >> .env.local
fi

if [ ! -z "$FACE_PLUS_PLUS_API_SECRET" ]; then
  echo "FACE_PLUS_PLUS_API_SECRET=$FACE_PLUS_PLUS_API_SECRET" >> .env.local
fi

if [ ! -z "$MAPBOX_API" ]; then
  echo "MAPBOX_API=$MAPBOX_API" >> .env.local
fi

if [ ! -z "$OPENWEATHERMAP_API_KEY" ]; then
  echo "OPENWEATHERMAP_API_KEY=$OPENWEATHERMAP_API_KEY" >> .env.local
fi

# Warm up Symfony cache
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# Fix permissions
chown -R www-data:www-data var

# Execute passed command
exec "$@"
