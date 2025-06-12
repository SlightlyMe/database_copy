# 🗄️ Database Structure Analyzer & Cloner

**Professional database documentation and cloning tools for PHP developers**

Generate AI-friendly database documentation and create realistic dummy data for development environments. Perfect for team collaboration, AI assistance, and secure development workflows.

## ✨ Features

- 🔍 **Smart Database Analysis** - Automatically maps your database structure, relationships, and constraints
- 🤖 **AI-Optimized Documentation** - Generates `.db_structure` files perfect for ChatGPT, Claude, and other AI assistants  
- 🎭 **Intelligent Dummy Data** - Creates realistic test data based on column names and types
- 🔒 **Secure by Design** - Uses `.env` files to keep credentials safe
- 🚀 **Zero Dependencies** - Pure PHP, works with any existing project
- 📊 **Multiple Output Modes** - Hide or show sensitive data with simple flags
- 🌐 **Universal SQL Support** - Works with MySQL, PostgreSQL, SQLite, and more

## 🚦 Quick Start

### 1. Download the Tools

```bash
# Download the main files
wget https://raw.githubusercontent.com/yourusername/db-tools/main/generate_db_structure.php
wget https://raw.githubusercontent.com/yourusername/db-tools/main/safe_clone_db.php
wget https://raw.githubusercontent.com/yourusername/db-tools/main/config.php
```

### 2. Create Your Environment File

```bash
# Copy the example and customize
cp .env.example .env
```

**Edit your `.env` file:**
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password

# Optional: API Keys
STRIPE_API_KEY=sk_test_your_stripe_key
GOOGLE_MAPS_API_KEY=your_google_maps_key
```

### 3. Secure Your Setup

**Add to `.gitignore`:**
```gitignore
# Never commit these!
.env
.env.local
.env.production
```

**Protect with `.htaccess` (Apache):**
```apache
<Files ".env*">
    Order allow,deny
    Deny from all
</Files>
```

## 📖 Usage

### Generate Database Documentation

```bash
# Generate structure (hides sensitive data by default)
php generate_db_structure.php

# Include row counts and sample data
php generate_db_structure.php show
```

**Output: `.db_structure` file**
```
DATABASE STRUCTURE REFERENCE
Generated: 2025-06-11 14:30:00
Database: my_ecommerce_app
================================================================================

TABLE OF CONTENTS:
  - users
  - products  
  - orders

DATABASE SUMMARY:
Total Tables: 3
Total Rows: [Hidden - use 'show' parameter to display]

TABLE: users
----------------------------------------
Description: Customer accounts and profiles
Engine: InnoDB

COLUMNS:
Name                 Type                      Null     Key      Default
----------------------------------------------------------------------------------------------------
id                   int(11)                   NO       PRI      NULL
email                varchar(255)              NO       UNI      NULL
name                 varchar(255)              NO                NULL

FOREIGN KEYS:
  orders.user_id -> users.id

SAMPLE DATA: [Hidden - use 'show' parameter to display actual data]
```

### Clone Database with Dummy Data

```bash
# Generate clone script (25 records per table)
php safe_clone_db.php

# Custom number of records
php safe_clone_db.php 100
```

**Output: `database_clone.sql`**
```sql
-- Database Clone Script with Dummy Data
-- Generated: 2025-06-11 14:30:00

CREATE DATABASE IF NOT EXISTS `my_app_clone`;
USE `my_app_clone`;

-- Table structure and realistic dummy data
INSERT INTO `users` (`name`, `email`, `phone`) VALUES 
('John Smith', 'john.smith123@example.com', '555-234-5678'),
('Mary Johnson', 'mary.johnson456@test.org', '555-345-6789');
```

## 🎯 Use Cases

### For AI Assistance
```bash
# Generate clean documentation
php generate_db_structure.php

# Then paste .db_structure into ChatGPT/Claude:
# "Here's my database structure. Help me write a query to..."
```

### For Development Teams
```bash
# New team member setup:
git clone project
cp .env.example .env
# Edit .env with local credentials
php generate_db_structure.php
php safe_clone_db.php 50
mysql -u root -p < database_clone.sql
```

### For Testing & CI/CD
```bash
# Generate test database
php safe_clone_db.php 1000
docker exec mysql mysql -u root -p < database_clone.sql
# Run your test suite with realistic data
```

## 🧠 Smart Data Generation

The cloner intelligently generates realistic data based on column names:

| Column Pattern | Generated Data |
|----------------|----------------|
| `email` | `john.doe123@example.com` |
| `first_name` | `Sarah` |
| `phone` | `555-123-4567` |
| `address` | `1234 Main Street` |
| `company` | `TechCorp Solutions` |
| `description` | `Lorem ipsum dolor sit amet...` |
| `password` | `$2y$10$...` (fake bcrypt hash) |

**Plus automatic handling of:**
- ✅ Foreign key relationships
- ✅ ENUM value selection  
- ✅ Proper data types and constraints
- ✅ Realistic date ranges
- ✅ Appropriate text lengths

## 🔐 Security Features

### Environment Variables
- **Never hardcode credentials** - everything comes from `.env`
- **Multiple environment support** - dev, staging, production
- **Safe for version control** - `.env` files are gitignored

### Sensitive Data Protection
```bash
# Safe for sharing (default)
php generate_db_structure.php
# Result: No row counts, no sample data

# Full details (development only)
php generate_db_structure.php show  
# Result: Includes actual data
```

### Server Protection
```apache
# Recommended .htaccess rules
<Files ".env*">
    Order allow,deny
    Deny from all
</Files>

<Files "generate_db_structure.php">
    Order allow,deny
    Deny from all
</Files>

<Files "safe_clone_db.php">
    Order allow,deny
    Deny from all
</Files>
```

## 📁 File Structure

```
your-project/
├── .env                          # Your credentials (NOT in git)
├── .env.example                  # Template (safe to commit)
├── .gitignore                    # Includes .env
├── config.php                    # Database connection handler
├── generate_db_structure.php     # Documentation generator
├── safe_clone_db.php            # Dummy data generator
├── .db_structure                 # Generated documentation
└── database_clone.sql            # Generated clone script
```

## 🔧 Configuration Options

### Browser Usage
```
# Documentation generator
http://localhost/generate_db_structure.php

# With sensitive data
http://localhost/generate_db_structure.php?sensitive=show

# Clone generator  
http://localhost/safe_clone_db.php?records=100
```

### Multiple Environments
```bash
# Development
cp .env.development .env
php generate_db_structure.php

# Staging  
cp .env.staging .env
php generate_db_structure.php

# Production (documentation only)
cp .env.production .env
php generate_db_structure.php  # Never run cloner on production!
```

## ⚠️ Important Security Notes

1. **Never run the cloner on production databases**
2. **Always use `.env` files for credentials**  
3. **Protect PHP files from web access**
4. **Review generated SQL before importing**
5. **Use the 'show' parameter only in development**

## 🤝 Contributing

Found a bug? Want to add a feature? Pull requests welcome!

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🎉 Acknowledgments

- Inspired by the need for better database documentation in PHP projects
- Built for the AI-assisted development era
- Designed with security and team collaboration in mind

---

**⭐ Star this repo if it helped you!**
