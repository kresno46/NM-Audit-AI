# Audittest Final

## About This Project
Audittest Final is a Laravel 12 based audit management system that leverages Livewire for dynamic user interfaces and includes chatbot integration for interactive audit sessions. The system supports audit session management, employee selection, interview conduction, reporting, and analytics. It also includes user authentication and role-based permissions.

## Folder Structure

- **app/**  
  Contains the core application code including HTTP controllers, Livewire components, models, and services.

- **bootstrap/**  
  Contains the application bootstrap files and cache.

- **config/**  
  Configuration files for the application.

- **database/**  
  Database migrations, seeders, and factories.

- **public/**  
  Publicly accessible files such as index.php, JavaScript, CSS, and images.

- **resources/**  
  Views (Blade templates), CSS, JavaScript, and other frontend assets.

- **routes/**  
  Route definitions for web, API, admin, auth, and other route groups.

- **storage/**  
  Storage for logs, cache, sessions, and compiled views.

- **tests/**  
  Automated tests for the application.

## How to Run

1. **Clone the repository**  
   ```bash
   git clone <repository-url>
   cd audittest-final
   ```

2. **Install PHP dependencies**  
   Make sure you have PHP 8.2 or higher installed. Then run:  
   ```bash
   composer install
   ```

3. **Install Node dependencies** (for frontend assets)  
   ```bash
   npm install
   ```

4. **Set up environment file**  
   Copy the example environment file and configure your environment variables:  
   ```bash
   cp .env.example .env
   ```
   Update `.env` with your database credentials and other settings.

5. **Generate application key**  
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations and seeders**  
   ```bash
   php artisan migrate --seed
   ```

7. **Build frontend assets**  
   ```bash
   npm run dev
   ```

8. **Serve the application**  
   ```bash
   php artisan serve
   ```
   The application will be accessible at `http://localhost:8000`.

## Additional Notes

- The project uses Laravel Sanctum for API authentication.
- Livewire components are used extensively for reactive UI.
- Role and permission management is handled by Spatie Laravel Permission package.
- Chatbot functionality is implemented with custom controllers and JavaScript.
