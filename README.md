# Input Sanitizer

**Input Sanitizer** protects your application by blocking malicious input data in requests.

Fully integrated with the [Fortify](https://octobercms.com/plugin/wobqqq-fortify) security system, it adds an extra layer of defense against XSS and injection attacks.

## 📊 Security Dashboard Widget

Fortify includes a built-in dashboard widget that gives you a real-time overview of your system’s security status.

- Highlights critical vulnerabilities and misconfigurations
- Provides quick access to all security checks and tools
- Helps you identify and fix issues in one place

This widget acts as a central hub, allowing you to monitor and manage your application's security at a glance.

## 🚀 Features

- Detects and blocks malicious payloads
- Sanitizes request input and headers
- Protects against XSS and injection attacks
- Lightweight and efficient filtering

## 🔗 Related Plugins

- [Fortify](https://octobercms.com/plugin/wobqqq-fortify) – comprehensive security suite
- [Admin IP Access](https://octobercms.com/plugin/wobqqq-fortifyadminipaccess) – restrict admin panel access by IP
- [IP Blocker](https://octobercms.com/plugin/wobqqq-fortifyipblocker) – manually block specific IP addresses
- [Smart IP Blocker](https://octobercms.com/plugin/wobqqq-fortifysmartipblocker) – automatic IP blocking based on request rate
- [CSP](https://octobercms.com/plugin/wobqqq-fortifycsp) – add Content Security Policy headers to prevent XSS

## 📦 Requirements

- PHP 8.2 or higher
- October CMS 3.0 or higher

## 💻 Usage

All configuration and management is handled via the October CMS admin panel.

**Admin Panel:**
Navigate to `Settings -> Fortify` and enable **Input Sanitizer**.

**Console Commands:**

- Disable Input Sanitizer:
```bash
php artisan wobqqq.fortify:input-sanitizer:disable
