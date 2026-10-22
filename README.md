# Symfony Maintenance Bundle

A robust, modern, and fully configurable Symfony 8.1+ bundle to manage application maintenance mode.

It supports multi-level configuration, cryptographic protection via HMAC signing (using `kernel.secret`), IP address and user role restrictions, as well as automatic secure cookie bypass generation mapped directly to user roles.

---

## 📋 Table of Contents

1. [Global Concept](#1-global-concept)
2. [Installation & Integration](#3-installation--integration)
3. [YAML Configuration](#4-yaml-configuration)
4. [Console Commands](#5-console-commands)
5. [Displaying the Maintenance Banner](#6-displaying-the-maintenance-banner)

---

## 1. Global Concept

This bundle allows you to put your application in maintenance mode without interrupting the work of your administrators or support teams.

### Key Features:
- **Modular Maintenance Levels**: Configure multiple levels (e.g., `default`, `admin`, `help_desk`) with custom permissions and templates.
- **Automatic Role-Based Cookie Bypass**: Automatically generates a cryptographically signed secure cookie when an authorized user accesses the application.
- **Role-Based Lifetimes**: Configure cookie duration globally per user role (e.g., 7 days for `ROLE_ADMIN`, 30 days for `ROLE_HELP_DESK`).
- **Hybrid Restrictions**: Flexible combination of rules by IP address, user roles (`ROLE_ADMIN`, `ROLE_SUPPORT`), and/or cookies.
- **Persistent Warning Banner (UX)**: Displays a customizable banner for authorized users navigating during maintenance.

---

## 2. Installation & Integration

### A. Download the Bundle

Just by running this command in your terminal : 

```bash
composer require mathis-vincent/maintenance-bundle
```

### B. Register the Bundle (`config/bundles.php`)

Ensure the bundle is registered in your `config/bundles.php` file:

```php
return [
    // ...
    MathisVincent\MaintenanceBundle\MaintenanceBundle::class => ['all' => true],
];
```

### C. Base Template Integration (`templates/base.html.twig`)

To display the maintenance warning banner to authorized users while maintenance is active, insert the Twig function at the top of your HTML document body:

```twig
<!DOCTYPE html>
<html>
    <head>
        <title>{% block title %}My Application{% endblock %}</title>
    </head>
    <body>
        {# Automatic display of the maintenance banner #}
        {{ maintenance_banner() }}

        {% block body %}{% endblock %}
    </body>
</html>
```

---

## 3. YAML Configuration

Create or edit the file `config/packages/maintenance.yaml` to define role lifetimes and access rules:

```yaml
maintenance:
  # Global cookie lifetimes per role (in seconds)
  role_lifetimes:
    ROLE_ADMIN: 604800      # 7 days
    ROLE_HELP_DESK: 2592000 # 30 days

  levels:
    # 1. Default Level: Blocks all visitors without exception
    default:
      template: "maintenance/503.html.twig"

    # 2. Admin Level: Allows specified IPs and ROLE_ADMIN users
    admin:
      allowed_ip:
        - '%env(ADMIN_IP)%'
      allowed_roles:
        - 'ROLE_ADMIN'
      template: "maintenance/503.html.twig"
      banner_template: "maintenance/banner.html.twig"

    # 3. Help Desk Level: Extends access to support teams
    help_desk:
      allowed_ip:
        - '%env(ADMIN_IP)%'
        - '%env(HELP_DESK_IP)%'
      allowed_roles:
        - 'ROLE_ADMIN'
        - 'ROLE_SUPPORT'
      template: "maintenance/503.html.twig"
      banner_template: "maintenance/banner.html.twig"
```

### Configuration Options:

| Option            | Type     | Description                                                                    |
|:------------------|:---------|:-------------------------------------------------------------------------------|
| `role_lifetimes`  | `array`  | Key-value mapping of Symfony user roles to bypass cookie lifetimes in seconds. |
| `allowed_ip`      | `array`  | List of IP addresses allowed to bypass maintenance mode.                       |
| `allowed_roles`   | `array`  | List of Symfony roles allowed to bypass maintenance mode.                      |
| `template`        | `string` | Twig template path for the 503 HTTP response displayed to blocked users.       |
| `banner_template` | `string` | Twig template path for the warning banner displayed to authorized users.       |

---

## 4. Console Commands

The bundle provides two console commands under the `app:maintenance:*` namespace.

### A. Activate Maintenance Mode

```bash
# Activate maintenance mode with default level ("default")
php bin/console app:maintenance:activate

# Activate maintenance mode for a specific level (e.g., "admin")
php bin/console app:maintenance:activate admin
```

> **Validation**: Activating an undeclared level (e.g., `php bin/console app:maintenance:activate non_existent`) will raise an error and fail gracefully.

### B. Deactivate Maintenance Mode

```bash
php bin/console app:maintenance:deactivate
```

---

## 5. Displaying the Maintenance Banner

The Twig extension (`MaintenanceTwigExtension`) provides three global functions:

- **`{{ maintenance_banner() }}`**: Renders the configured banner template (`banner_template`) if maintenance mode is active.
- **`{{ maintenance_is_active() }}`**: Returns `true` if maintenance mode is currently active.
- **`{{ maintenance_level() }}`**: Returns the name of the active maintenance level (e.g., `admin`).

### Behavior Overview:
- **Normal Mode**: `{{ maintenance_banner() }}` returns an empty string.
- **Maintenance Active (`app:maintenance:activate admin`)**:
  - Unauthenticated or unauthorized visitors receive the 503 HTTP status page.
  - Authorized users (via IP, role, or HMAC cookie) navigate seamlessly and automatically receive a signed cookie mapped to their role duration.