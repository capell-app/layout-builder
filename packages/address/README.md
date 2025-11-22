# Capell Address

Simple address and country management for Capell. This package provides reusable form components, Filament resources, and relationships to attach an address and country to your core `Site` model.

## Overview

- Form inputs for selecting a country and composing an address
- Filament admin resources to manage Countries and Addresses
- Schema extenders to surface address fields on Site forms
- Install and demo commands to get you started quickly

## Features

- Form components
  - `Capell\Address\Filament\Components\Forms\CountrySelect`
  - `Capell\Address\Filament\Components\Forms\AddressSelect`
- Filament resources and tables
  - Countries: list, create, edit
  - Addresses: list, create, edit
- Schema integration
  - Site schema extender integrates address fields into Site manage forms
- Model relationships to `Site`
  - `Site::address()` and `Site::country()` are registered at runtime
- Commands
  - `capell-address:install` — publish migrations and migrate
  - `capell-address:demo` — optional demo data

## Installation

Prerequisites:
- Capell Admin package must be installed/enabled.

Steps:
1. Install and run the installer:
   ```bash
   php artisan capell-address:install
   ```
   This will:
   - Register Filament resources and permissions
   - Publish package migrations for countries and addresses
   - Run the migrations

2. (Optional) Seed demo data:
   ```bash
   php artisan capell-address:demo
   ```

## Database

This package ships migrations for two tables:
- `create_countries_table.php`
- `create_addresses_table.php`

Factories are included for faster testing/development.

See the extra docs for details and references:
- Database reference: [docs/Database.md](docs/Database.md)
- API reference: [docs/API.md](docs/API.md)
