# Form Generator V2.0

Form Generator is a modern, high-performance Laravel 12 + Livewire 3 application for building custom forms, sharing public form links, collecting submissions, and analyzing responses with advanced AI-driven features.

The application leverages **Google Gemini AI** to enable interactive chat-based form creation, automated anomaly detection, sentiment analysis of submissions, and intelligent dashboard summaries.

---

## Key Features

### 🛠️ Dynamic Form Builder
- **Visual Drag-and-Drop Builder**: Arrange, edit, and organize forms with instant live previews.
- **Supported Fields**: Heading, Paragraph, Divider, Text, Email, Number, Phone, URL, Textarea, Select, Radio, Checkbox, Date, File, and Image.
- **Conditional Logic**: Configure fields to show or hide dynamically based on responses to other inputs.
- **Multi-Page Layouts**: Organize long forms into structured step-by-step pages.

### 🤖 Google Gemini AI Integration (New in v2.0)
- **AI Form Builder**: Create, modify, and expand form schemas iteratively using natural language in the sidebar chat.
- **Sentiment Analysis**: Evaluates open-ended answers to flag overall sentiment (`positive`, `neutral`, `negative`), calculate intensity scores (`0.0` to `1.0`), and detect emotional sub-tones (e.g., *frustrated*, *excited*, *satisfied*). Processed asynchronously in the background via queues.
- **Anomalies & Alert Engine**: Automatically scans form submissions to flag:
  - **Significant Traffic Drop**: Alerts when weekly submissions drop by 70% or more.
  - **Submission Traffic Spike**: Alerts when submissions spike by 150% or more.
  - **Negative Feedback Warning**: Alerts when negative sentiment submissions exceed 40% of the total reviews over 7 days.
  - **Form Going Quiet**: Alerts when a historically active form receives zero submissions for 14 consecutive days.
- **Dashboard Health Digest**: Generates a natural-language summary overview of system performance and activity when no critical anomalies are detected.

### 📊 Dashboard & Analytics
- **Submission Tracking**: View, search, and delete submissions.
- **Visual Analytics**: Dynamic trends, submission counts, and custom analytics graphs.
- **Excel/CSV Export**: Export all submission data instantly.
- **Automatic Attachment Cleanup**: Deleting a form submission automatically sweeps and deletes all associated file/image attachments from storage.

### 🔒 Security & Auth
- **Google OAuth Login**: Sign in securely using Google accounts.
- **Email Verification**: Multi-step verification flow with styled email templates.
- **Secure Submissions**: Throttle protections, validation constraints, and secure signed URLs for file access.
- **Offline Syncing**: Secure endpoints allow submissions to be staged locally and synced once a connection is established.

---

## Tech Stack

- **Backend**: PHP 8.2+, Laravel 12
- **Frontend**: Livewire 3, AlpineJS, Tailwind CSS, Flux UI Components
- **Database**: MySQL (Primary storage), Redis (Optional, recommended for caching/queues in production)
- **AI Service**: Google Gemini API (via HTTP client, configured for cost-efficient caching)
- **Mailing**: SMTP (Default/Brevo) / Resend API integration

---

## Quick Start

### Prerequisites
Install the following on your system:
- **PHP 8.2+**
- **Composer**
- **Node.js 20+** and **Bun** (or NPM)
- **MySQL 8+**

### Installation

1. **Clone the Repository** and navigate to the project directory:
   ```bash
   git clone <repository-url>
   cd form-generator
   ```

2. **Run the Automated Setup**:
   The project includes a Composer setup script that installs PHP dependencies, copies your environment configuration, generates keys, runs migrations, installs JS modules, and compiles assets:
   ```bash
   composer run setup
   ```

3. **Database Seeding (Test User Included)**:
   Seed the database with realistic forms, multi-page schemas, and simulated submissions (with pre-analyzed AI sentiment):
   ```bash
   php artisan db:seed
   ```
   > [!IMPORTANT]
   > Seeding creates a default administrator user:
   > - **Email**: `qwe@qwe.qwe`
   > - **Password**: `qwerqwer`

4. **Start the Development Services**:
   Run all services concurrently (Vite dev server, queue worker, and local PHP server):
   ```bash
   composer run dev
   ```
   *Alternatively, run them manually in separate terminal windows:*
   ```bash
   php artisan serve
   php artisan queue:listen --tries=1
   bun run dev
   ```

5. **Run the Test Suite**:
   Execute the full suite of unit and feature tests:
   ```bash
   composer run test
   ```

---

## Environment Configuration

Copy `.env.example` to `.env` and fill in the required values.

### Core Settings
- `APP_NAME`: Name of the application.
- `APP_ENV`: Deployment environment (`local` or `production`).
- `APP_KEY`: Application key (run `php artisan key:generate` to generate).
- `APP_URL`: Complete public domain URL (e.g. `http://localhost:8000` or `https://yourdomain.com`).

### Database
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=form_generator`
- `DB_USERNAME=root`
- `DB_PASSWORD=`

### Google Gemini AI Config
To use the interactive form builder, sentiment analysis, and dashboard digests, obtain a free or pay-as-you-go API key from [Google AI Studio](https://aistudio.google.com/).

- `GEMINI_API_KEY`: Your Google AI Studio API key.
- `GEMINI_MODEL`: Target AI model (defaults to `gemini-2.5-flash`).
- `AI_INSIGHTS_ENABLED`: Toggle natural language form insights (default: `true`).
- `AI_SENTIMENT_ENABLED`: Toggle sentiment analysis on text/textarea fields (default: `true`).
- `AI_ANOMALIES_ENABLED`: Toggle automated anomaly alert generation (default: `true`).
- `AI_FORM_BUILDER_ENABLED`: Toggle conversational sidebar builder (default: `true`).
- `AI_CACHE_TTL`: Cache duration in seconds for generated insights to control API costs (default: `43200` [12 hours]).
- `AI_MAX_SUBMISSIONS`: Max submissions sent to Gemini per analytics payload (default: `250`).

### Mail Integration (for Verification & Alerts)
For transactional emails, configure your SMTP server or use the built-in Resend SDK.

#### Resend Integration
- `MAIL_MAILER=resend`
- `RESEND_KEY`: Your Resend API Key (also falls back to `RESEND_API_KEY` if provided).
- `MAIL_FROM_ADDRESS`: A domain verified in your Resend account.

#### Standard SMTP (e.g., Brevo/Sendgrid)
- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp-relay.brevo.com`
- `MAIL_PORT=587`
- `MAIL_SCHEME=smtp` (use `smtps` for SSL port 465)
- `MAIL_USERNAME`: Your provider SMTP login.
- `MAIL_PASSWORD`: Your provider SMTP password key.

### Google OAuth
Required if you want to enable the "Sign in with Google" button:
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`: Set to `${APP_URL}/auth/google/callback`

---

## Main Routes Reference

### Public Routes
- `/` — Application landing page.
- `/terms` — Terms of Service agreement.
- `/privacy` — Privacy Policy document.
- `/f/{slug}` — Public form view page.
- `/f/{slug}/manifest.json` — Offline manifest for form caching.

### Authentication
- `/register` — Account registration.
- `/login` — User authentication.
- `/verify-email` — Verification notice.
- `/auth/google` — Redirect to Google OAuth.
- `/auth/google/callback` — Google callback handler.

### Authenticated Workspace (`/dashboard`)
- `/dashboard` — Interactive dashboard, analytics panels, profile, settings, and form lists.
- `/dashboard/intelligence` — Fetches anomaly alerts, trends, and digest logs.
- `/forms/create` — Access the form builder workspace (includes AI Chat sidebar).
- `/forms/{formId}/edit` — Edit existing form settings, schemas, and logic.
- `/submissions/export` — Download submission database records as CSV.

---

## File Uploads & Storage

1. Uploaded files (e.g. photos, resumes) are securely written to the `submissions/` directory under your default storage disk.
2. File attachments are served through a controller to ensure authentication controls and download token integrity.
3. To enable file access locally, run:
   ```bash
   php artisan storage:link
   ```
4. Submissions cleanup is automated. Deleting a submission automatically purges all files uploaded with it, protecting disk storage.

---

## Deployment & Production Best Practices

- **Configuration Caching**: Always warm up configurations on release:
  ```bash
  php artisan optimize
  ```
- **Force Migrations**: Apply DB changes during deployment pipelines:
  ```bash
  php artisan migrate --force
  ```
- **Queue Workers**: Ensure a persistent queue worker is configured (e.g., Supervisor-managed `php artisan queue:work`) to handle sentiment analysis, offline synchronizations, and email dispatches asynchronously.
- **HTTPS Handling**: The system trusts upstream proxy headers by default. Set `APP_URL` using the strict `https://` prefix to prevent signed URL and redirection issues.

---

## License

This project is open-sourced under the [MIT License](LICENSE).
