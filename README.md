# NM Audit AI - Audit Management System

## Overview
NM Audit AI is a comprehensive audit management system built with Laravel 12, featuring AI-powered audit assistance, user management, and detailed reporting capabilities. The system streamlines the audit process from planning to execution and reporting.

## 🚀 Features

### Core Features
- **AI-Powered Audit Assistant** - Intelligent chatbot for audit guidance
- **Comprehensive User Management** - Role-based access control
- **Audit Session Management** - Plan, execute, and track audit sessions
- **Employee Interview System** - Structured interview process
- **Dynamic Reporting** - Real-time audit reports and analytics
- **Activity Logging** - Complete audit trail

### User Management
- **Multi-role System**: Administrator, Auditor, Manager
- **User Profile Management** - Complete profile with employee details
- **Permission-based Access** - Granular access control
- **User Activity Tracking** - Monitor user actions

### Audit Management
- **Session Planning** - Schedule and plan audit sessions
- **Employee Selection** - Dynamic employee selection for audits
- **Interview Conduction** - Structured interview process with AI assistance
- **Answer Management** - Store and analyze audit responses
- **Result Analysis** - Comprehensive audit result analysis

### Admin Panel
- **Dashboard Overview** - System statistics and metrics
- **User Management** - Create, edit, and manage users
- **Audit Monitoring** - Track ongoing audit sessions
- **System Configuration** - Manage system settings

## 🛠️ Technology Stack

- **Backend**: Laravel 12.x
- **Frontend**: Blade Templates, Livewire, Alpine.js
- **Database**: MySQL/PostgreSQL
- **Authentication**: Laravel Sanctum
- **Authorization**: Spatie Laravel Permission
- **AI Integration**: OpenAI API
- **Real-time**: Laravel Echo (optional)

## 📋 Requirements

### System Requirements
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+ or PostgreSQL 13+
- Web Server (Apache/Nginx)

### PHP Extensions
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML

## 🚀 Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd NM-Audit-AI
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Configuration
Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nm_audit_ai
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Database Migration & Seeding
```bash
# Run migrations
php artisan migrate

# Seed initial data
php artisan db:seed
```

### 6. Build Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

### 7. Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application.

## 🔧 Configuration

### OpenAI Configuration
Add your OpenAI API key to `.env`:
```env
OPENAI_API_KEY=your_openai_api_key
OPENAI_ORGANIZATION=your_organization_id
```

### Mail Configuration (Optional)
For email notifications:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

## 📖 Usage

### Initial Setup
1. After installation, login with default admin credentials:
   - Email: admin@example.com
   - Password: password

2. Navigate to Admin Panel to:
   - Create new users
   - Configure system settings
   - Manage audit templates

### Creating Audit Sessions
1. Go to Audit → Create Session
2. Select employees to audit
3. Schedule audit date
4. Assign auditors

### Conducting Audits
1. Access active audit sessions
2. Use AI assistant for guidance
3. Conduct employee interviews
4. Record responses systematically

### Generating Reports
1. Access completed audit sessions
2. Generate comprehensive reports
3. Export results in various formats

## 🗂️ Project Structure

```
NM-Audit-AI/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin panel controllers
│   │   │   ├── Api/            # API controllers
│   │   │   └── Auth/           # Authentication controllers
│   │   └── Livewire/           # Livewire components
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Business logic services
│   └── Providers/              # Service providers
├── database/
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── resources/
│   ├── views/
│   │   ├── admin/              # Admin panel views
│   │   ├── audit/              # Audit system views
│   │   ├── profile/            # User profile views
│   │   └── components/         # Reusable components
│   └── js/                     # JavaScript assets
├── routes/
│   ├── web.php                 # Web routes
│   ├── api.php                 # API routes
│   └── admin.php               # Admin routes
└── storage/
    ├── logs/                   # Application logs
    └── framework/              # Framework cache
```

## 🔐 Security Features

- **Role-based Access Control** (RBAC)
- **Password Hashing** (bcrypt)
- **CSRF Protection**
- **SQL Injection Prevention**
- **XSS Protection**
- **Rate Limiting**

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature

# Generate coverage report
php artisan test --coverage
```

## 📊 API Documentation

### Authentication Endpoints
- `POST /api/login` - User login
- `POST /api/register` - User registration
- `POST /api/logout` - User logout

### Audit Endpoints
- `GET /api/audit-sessions` - List audit sessions
- `POST /api/audit-sessions` - Create new session
- `GET /api/audit-sessions/{id}` - Get session details
- `POST /api/audit-sessions/{id}/start` - Start audit
- `POST /api/audit-sessions/{id}/complete` - Complete audit

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, email support@nmaudit.com or join our Slack channel.

## 🔄 Changelog

### Version 1.0.0
- Initial release
- User management system
- Audit session management
- AI-powered chatbot
- Admin panel
- Reporting system
