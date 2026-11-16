# 🎓 StageTunisie (MonStage)

A web platform for **finding and managing internships in Tunisia**. Students browse offers and companies, apply online (CV + cover letter) and track their applications; administrators publish offers, manage companies and process applications.

> Built by **Fatma Moussa**, engineering student at ISIMM (year 1).

---

## ✨ Features

### 👩‍🎓 Student side
- Secure sign-up / login (passwords hashed with `password_hash`)
- Browse **internship offers** and **company profiles**
- **Online applications** with CV and cover letter upload
- Dashboard to follow the **status** of each application
- **Reviews and ratings (1 to 5)** for companies

### 🛠️ Admin side
- Admin dashboard
- Full **CRUD for internship offers**
- **Company management** (add with logo, list, delete)
- **User management**
- **Application processing**: update the status of each application
- **PDF export** of filtered applications, with **sending by e-mail**

### 📚 Content
- **Blog** with 8 advice articles (CV, cover letter, interview...)
- A dedicated **AI Coach** page to support applicants

---

## 🧱 Tech stack

| Layer | Technology |
|---|---|
| Back end | PHP (procedural) |
| Database | PostgreSQL (`pg_connect`, parameterized queries `$1, $2...`) |
| Front end | HTML, CSS (`style.css`) |
| PDF generation | [FPDF](http://www.fpdf.org/) |
| E-mail | [PHPMailer 7.0.2](https://github.com/PHPMailer/PHPMailer) (SMTP) |

---

## 🗂️ Project structure

```
MonStage/
├── index.php                  # Home page (login / sign-up)
├── nosoffres.php              # Offers list
├── nosentreprises.php         # Companies list
├── blog.php, conseil1-8.php   # Blog and advice articles
├── ai.php                     # AI Coach
├── etudiant*.php              # Student area
├── admin*.php                 # Admin area
├── generate_pdf.php           # PDF generation (FPDF)
├── send_pdf.php               # PDF sending by e-mail (PHPMailer)
├── database/schema.sql        # PostgreSQL schema
├── config.example.php         # Database connection template
├── config_mail.example.php    # SMTP configuration template
├── images/, uploads/          # Media and uploaded files
└── style.css
```

### Data model

`utilisateurs` (admin / student) · `entreprises` · `offres_stage` · `candidatures` (status, CV, cover letter) · `avis` (rating 1-5)

---

## 🚀 Getting started

### Requirements
- PHP 8+ with the **pgsql** extension enabled
- PostgreSQL

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/<your-account>/MonStage.git
cd MonStage

# 2. Create the database and import the schema
createdb -U postgres MonStage
psql -U postgres -d MonStage -f database/schema.sql

# 3. Configure the database connection
cp config.example.php config.php        # then edit config.php

# 4. (Optional) Configure e-mail sending
cp config_mail.example.php config_mail.php   # then edit config_mail.php

# 5. Start the development server
php -S localhost:8000
```

On Windows (cmd), use `copy` instead of `cp`. The admin pages expect an `adminphoto.webp` image: you can use any picture, for example `copy inconnu.png adminphoto.webp`.

Then open <http://localhost:8000>.

### Create an admin account

Generate a hashed password:

```bash
php -r "echo password_hash('MyPassword', PASSWORD_DEFAULT);"
```

Then insert it:

```sql
INSERT INTO utilisateurs (nom, email, password, role)
VALUES ('Admin', 'admin@example.com', '<generated hash>', 'admin');
```

---

## 🔒 Security

- `config.php`, `config_mail.php` and the `uploads/` folder are **excluded from Git** (`.gitignore`)
- Hashed passwords (`password_hash` / `password_verify`)
- Parameterized SQL queries
- For Gmail, use an **app password**, never your account password

---

## 🛣️ Possible improvements

- Factor out the login/sign-up code, currently repeated in several pages (shared `include`)
- Add **CSRF** protection to forms
- Restrict and validate uploaded file types (CV, logos)
- Move to an MVC structure or a framework (Laravel, Symfony)
- Manage PHPMailer and FPDF with **Composer**

---

## 👩‍💻 Author

**Fatma Moussa**, engineering student at ISIMM (year 1)
