# GoTrip - Multilingual Travel Booking Platform

![GoTrip Banner](public/assets/img/general/logo-light.svg)

GoTrip is a modern, feature-rich travel booking platform built with Symfony 6. The application provides a seamless experience for booking activities, destinations, and accommodations with full multilingual support.

## Features

- **Multilingual Support**: Full support for English, French, Arabic, and German
- **Dynamic Search**: Advanced search functionality for destinations and activities
- **User Authentication**: Secure login/registration system with different user roles
- **Activity Management**: Browse and book various travel activities
- **Destination Explorer**: Discover popular destinations with detailed information
- **Responsive Design**: Mobile-first approach ensuring compatibility across all devices
- **Admin Dashboard**: Comprehensive admin interface for content management

## Technology Stack

- **Backend**: Symfony 6, PHP 8.1+
- **Frontend**: HTML5, CSS3, JavaScript, Twig Templates
- **Database**: MySQL/MariaDB
- **Dependencies**: Composer, Symfony CLI

## Prerequisites

- PHP 8.1 or higher
- Composer
- Symfony CLI
- MySQL or MariaDB
- Node.js and NPM (for asset compilation)

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/username/PiProject.git
   cd PiProject
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Configure your environment**:
   - Copy `.env` to `.env.local` and update database credentials:
     ```
     DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0"
     ```

4. **Create the database and schema**:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create
   ```

5. **Load fixtures (optional)**:
   ```bash
   php bin/console doctrine:fixtures:load
   ```

6. **Start the Symfony development server**:
   ```bash
   symfony server:start
   ```

7. **Access the application**:
   Open your browser and navigate to `http://localhost:8000`

## Multilingual Support

GoTrip offers comprehensive multilingual support with translations for:

- **User Interface Elements**: Navigation, buttons, forms, and error messages
- **Content**: Destinations, activities, and general information
- **Emails**: Notification emails in the user's preferred language

### Available Languages

- English (Default)
- French
- Arabic
- German

### Adding a New Language

1. Create a new translation file in the `translations/` directory:
   ```bash
   touch translations/messages.XX.yaml  # Replace XX with language code
   ```

2. Add your translations following the existing structure in other language files
3. Update the language switcher in the footer to include the new language

## Project Structure

The project follows the standard Symfony directory structure with some custom directories:

```
PiProject/
├── bin/                  # Symfony binary files
├── config/               # Configuration files
├── migrations/           # Database migrations
├── public/               # Publicly accessible files (web root)
│   ├── assets/           # CSS, JS, and image files
│   └── uploads/          # User uploads
├── src/                  # PHP source code
│   ├── Controller/       # Application controllers
│   ├── Entity/           # Doctrine entities
│   ├── Form/             # Form types
│   ├── Repository/       # Doctrine repositories
│   └── Service/          # Application services
├── templates/            # Twig templates
│   ├── base.html.twig    # Base template
│   ├── client/           # Client-facing templates
│   ├── admin/            # Admin templates
│   └── security/         # Authentication templates
├── translations/         # Translation files
│   ├── messages.en.yaml  # English translations
│   ├── messages.fr.yaml  # French translations
│   ├── messages.ar.yaml  # Arabic translations
│   └── messages.de.yaml  # German translations
├── var/                  # Cache and logs
└── vendor/               # Dependencies
```

## Feature Details

### Multilingual System

The application uses Symfony's translation component with YAML files to manage translations. Key features include:

- Language selection preserved in user session
- RTL support for Arabic
- Translation keys organized by feature
- Dynamic content translation from database

### Search System

The search functionality allows users to find:

- Destinations by name, region, or country
- Activities by type, location, or price range
- Accommodations by features and amenities

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Development Team

### Amir Othman
- Email: amir.othman@esprit.tn
- GitHub: [amir-othman](https://github.com/amir-othman)
- LinkedIn: [amirothman](https://www.linkedin.com/in/amirothman/)

### Karim Makni
- GitHub: [karimmakni0](https://github.com/karimmakni0)

### Amir Jabeur
- GitHub: [SASASHHJ](https://github.com/orgs/Pi-Dev2025/people/SASASHHJ)

### Amin Gharbi
- GitHub: [AMG9980](https://github.com/AMG9980)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- [Symfony](https://symfony.com/)
- [Bootstrap](https://getbootstrap.com/)
- [Font Awesome](https://fontawesome.com/)
- [jQuery](https://jquery.com/)
- All contributors who have helped shape this project
