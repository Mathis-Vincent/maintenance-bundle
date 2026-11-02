<p align="center"><img src=".github/logo.svg" alt="Symfony Maintenance Bundle logo" width="120"></p>

# Symfony Maintenance Bundle

[![CI](https://github.com/Mathis-Vincent/maintenance-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/Mathis-Vincent/maintenance-bundle/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/mathis-vincent/maintenance-bundle.svg)](https://packagist.org/packages/mathis-vincent/maintenance-bundle)
[![PHP Version](https://img.shields.io/packagist/dependency-v/mathis-vincent/maintenance-bundle/php.svg)](https://packagist.org/packages/mathis-vincent/maintenance-bundle)
[![License](https://img.shields.io/github/license/Mathis-Vincent/maintenance-bundle.svg)](LICENSE)
[![Website](https://img.shields.io/badge/website-maintenance--bundle.mathis--vincent.com-blue.svg)](https://maintenance-bundle.mathis-vincent.com/)

A robust, modern, and fully configurable Symfony bundle to manage application maintenance mode.

It supports multi-level configuration, cryptographic protection via HMAC signing (using `kernel.secret`), IP address and user role restrictions, as well as automatic secure cookie bypass generation mapped directly to user roles.

**Requirements:** PHP 8.2+ and Symfony 6.4, 7.x or 8.x.

---

## 📋 Table of Contents

1. [Global Concept](#1-global-concept)
2. [Installation & Integration](#2-installation--integration)
3. [YAML Configuration](#3-yaml-configuration)
4. [Console Commands](#4-console-commands)
5. [Displaying the Maintenance Banner](#5-displaying-the-maintenance-banner)
6. [Reacting to Maintenance Changes](#6-reacting-to-maintenance-changes)
7. [Contributing](#7-contributing)
8. [License](#8-license)

---

## 1. Global Concept

This bundle allows you to put your application in maintenance mode without interrupting the work of your administrators or support teams.

### Key Features:
- **Modular Maintenance Levels**: Configure multiple levels (e.g., `default`, `admin`, `help_desk`) with custom permissions and templates.
- **Automatic Role-Based Cookie Bypass**: Automatically generates a cryptographically signed secure cookie when an authorized user accesses the application.
- **Cookies Reset Per Maintenance**: Each activation gets a unique ID signed into the cookie, so cookies from a previous maintenance stop working.
- **Role-Based Lifetimes**: Configure cookie duration globally per user role (e.g., 7 days for `ROLE_ADMIN`, 30 days for `ROLE_HELP_DESK`).
- **Hybrid Restrictions**: Flexible combination of rules by IP address, user roles (`ROLE_ADMIN`, `ROLE_SUPPORT`), and/or cookies.
- **Persistent Warning Banner (UX)**: Displays a customizable banner for authorized users navigating during maintenance.

### Why the state is a file, not a database
The maintenance state lives in `var/maintenance.json` (and `var/maintenance_announcement.json`), never in the database. Maintenance must stay available when the database is down: a database-backed state would make activation fail and every state check depend on it. The bundle therefore has no Doctrine dependency.

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

> **Level names are up to you.** `default`, `admin` and `help_desk` below are only examples: declare as many levels as you need under `levels`, with any name (e.g. `deploy`, `db_migration`, `partners_only`). The only special name is `default`, which `maintenance:activate` uses when no level is given.

```yaml
maintenance:
  # Global cookie lifetimes per role (in seconds)
  role_lifetimes:
    ROLE_ADMIN: 604800      # 7 days
    ROLE_HELP_DESK: 2592000 # 30 days

  levels:
    # Example levels — rename, remove or add your own.

    # "default": blocks all visitors without exception
    default:
      template: "maintenance/503.html.twig"

    # "admin" (example): allows specified IPs and ROLE_ADMIN users
    admin:
      allowed_ip:
        - '%env(ADMIN_IP)%'
      allowed_roles:
        - 'ROLE_ADMIN'
      excluded_routes:
        - 'app_home'
      template: "maintenance/503.html.twig"
      banner_template: "maintenance/banner.html.twig"

    # "help_desk" (example): extends access to support teams
    help_desk:
      allowed_ip:
        - '%env(ADMIN_IP)%'
        - '%env(HELP_DESK_IP)%'
      allowed_roles:
        - 'ROLE_ADMIN'
        - 'ROLE_HELP_DESK'
      template: "maintenance/503.html.twig"
      banner_template: "maintenance/banner.html.twig"
```

### Configuration Options:

| Option            | Type     | Description                                                                    |
|:------------------|:---------|:-------------------------------------------------------------------------------|
| `role_lifetimes`  | `array`  | Key-value mapping of Symfony user roles to bypass cookie lifetimes in seconds. |
| `allowed_ip`      | `array`  | List of IP addresses allowed to bypass maintenance mode.                       |
| `allowed_roles`   | `array`  | List of Symfony roles allowed to bypass maintenance mode.                      |
| `excluded_routes` | `array`  | Route names (exact match) that stay available to everyone at this level.       |
| `template`        | `string` | Twig template path for the 503 HTTP response displayed to blocked users.       |
| `banner_template` | `string` | Twig template path for the warning banner displayed to authorized users.       |

### Which roles to use:

The roles in `role_lifetimes` and `allowed_roles` are your Symfony security roles: the ones your users hold (`getRoles()`) and the ones you define under `role_hierarchy` in `config/packages/security.yaml`. The bundle does not create any role, and a role your users hold directly does not need to be listed in `role_hierarchy`.

```yaml
# config/packages/security.yaml
security:
  role_hierarchy:
    ROLE_HELP_DESK: ROLE_USER
    ROLE_ADMIN: ROLE_HELP_DESK
```

Access is checked with `isGranted()`, so the hierarchy applies. With the hierarchy above, `allowed_roles: ['ROLE_HELP_DESK']` also lets `ROLE_ADMIN` users through.

The cookie lifetime comes from the first role of `allowed_roles` that the user is granted, not from the role they actually hold. In that same example, a `ROLE_ADMIN` user gets the `ROLE_HELP_DESK` lifetime. To give them their own lifetime, list `ROLE_ADMIN` first in `allowed_roles`. A role without an entry in `role_lifetimes` gets 30 days.

---

## 4. Console Commands

The bundle provides four console commands under the `maintenance:*` namespace.

### A. Activate Maintenance Mode

```bash
# Activate maintenance mode with default level ("default")
php bin/console maintenance:activate

# Activate maintenance mode for any level declared in your config (e.g., "admin")
php bin/console maintenance:activate <level>

# End the maintenance by itself after a duration in seconds (here, 2 hours)
php bin/console maintenance:activate <level> --duration=7200
```

> **Validation**: Activating an undeclared level (e.g., `php bin/console maintenance:activate non_existent`) will raise an error and fail gracefully.

With `--duration`, the site reopens at the end date even if you can no longer reach the server: no cron or worker is involved, the date is checked on each request. The 503 response then carries a `Retry-After` header (seconds until the end date); without `--duration` it is omitted. The end date can be shown to visitors, see [Showing the end date](#showing-the-end-date).

### B. Deactivate Maintenance Mode

```bash
php bin/console maintenance:deactivate
```

### C. Check Maintenance Status

```bash
php bin/console maintenance:status
```

Displays whether maintenance mode is active, with which level and until when, and any announced maintenance. It also warns when the active level is not declared in your config, since requests are not blocked in that case. The command always exits with `0`.

### D. Announce a Maintenance

```bash
# Announce a date (anything PHP's strtotime() understands, in the server's timezone)
php bin/console maintenance:announce "2026-12-01 22:00"

# Remove the announcement
php bin/console maintenance:announce --cancel
```

An announcement only informs visitors, see [Showing an announcement](#showing-an-announcement): it does **not** start the maintenance, you still run `maintenance:activate` when the time comes. It disappears by itself once the date has passed, or when a maintenance is activated.

---

## 5. Displaying the Maintenance Banner

The Twig extension (`MaintenanceTwigExtension`) provides five global functions:

- **`{{ maintenance_banner() }}`**: Renders the configured banner template (`banner_template`) if maintenance mode is active. On a route listed in `excluded_routes`, it only renders for people who can bypass the level (IP, role or HMAC cookie).
- **`{{ maintenance_is_active() }}`**: Returns `true` if maintenance mode is currently active.
- **`{{ maintenance_level() }}`**: Returns the name of the active maintenance level (e.g., `admin`).
- **`{{ maintenance_end() }}`**: Returns the end date of the active maintenance as a `DateTimeImmutable`, `null` without `--duration`.
- **`{{ maintenance_announcement() }}`**: Returns the announced date as a `DateTimeImmutable`, `null` when nothing is announced.

### Behavior Overview:
- **Normal Mode**: `{{ maintenance_banner() }}` returns an empty string.
- **Maintenance Active (`maintenance:activate admin`)**:
  - Unauthenticated or unauthorized visitors receive the 503 HTTP status page.
  - Authorized users (via IP, role, or HMAC cookie) navigate seamlessly and automatically receive a signed cookie mapped to their role duration.

### Showing who can reach the page:

The banner template receives `status.page`, a translatable message saying who can reach the current route. Print it with the `trans` filter:

```twig
<div class="maintenance-banner">Maintenance in progress. This page is accessible by: {{ status.page|trans }}</div>
```

The bundle ships English and French translations in the `maintenance` domain. To change a text or add a language, declare the keys in your project, for example in `translations/maintenance.fr.yaml`:

```yaml
maintenance:
  status:
    page:
      everyone: 'tout le monde'       # excluded route
      nobody: 'personne'              # no allowed role or IP
      roles: '%roles%'                # allowed roles only
      ips: 'les IP autorisées'        # allowed IPs only
      roles_and_ips: '%roles%, les IP autorisées'
```

`%roles%` is the comma-separated list of the level's `allowed_roles`. IP values are never displayed.

### Showing the end date:

In your maintenance page (`template`) or banner:

```twig
{% if maintenance_end() %}
    <p>Back on {{ maintenance_end()|date('d/m/Y H:i') }}</p>
{% endif %}
```

### Showing an announcement:

In your base template, so that every visitor sees it:

```twig
{% if maintenance_announcement() %}
    <div class="maintenance-announcement">Maintenance planned on {{ maintenance_announcement()|date('d/m/Y H:i') }}</div>
{% endif %}
```

---

## 6. Reacting to Maintenance Changes

The bundle dispatches an event whenever the maintenance is activated, deactivated or announced (commands or `MaintenanceManager`). `endsAt` and `startsAt` are integer Unix timestamps. A maintenance ending by itself after `--duration` dispatches no event:

- `MathisVincent\MaintenanceBundle\Event\MaintenanceActivatedEvent` (`level`, `endsAt` or `null`)
- `MathisVincent\MaintenanceBundle\Event\MaintenanceDeactivatedEvent` (`level`), only when a maintenance was active
- `MathisVincent\MaintenanceBundle\Event\MaintenanceAnnouncedEvent` (`startsAt`)

```php
#[AsEventListener]
public function onActivated(MaintenanceActivatedEvent $event): void
{
    // notify the team, clear a cache...
}
```

---

## 7. Contributing

Contributions are welcome! Bug reports, feature ideas and pull requests can all be submitted on [GitHub](https://github.com/Mathis-Vincent/maintenance-bundle/issues).

The whole toolchain runs in Docker, so you only need Docker and `make`:

```bash
make install   # build the image and install dependencies
make ci        # coding style + static analysis + tests, like the CI
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full workflow.

---

## 8. License

This bundle is released under the [MIT License](LICENSE).
