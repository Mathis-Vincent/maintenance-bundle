# Contributing

Thanks for taking the time to contribute!

## Reporting a bug or suggesting a feature

Open an [issue](https://github.com/Mathis-Vincent/maintenance-bundle/issues) with:

- the bundle, PHP and Symfony versions you use;
- your `maintenance` configuration;
- what you expected and what happened instead.

Security issues should not be reported publicly: email contact@mathis-vincent.com instead.

## Development setup

Everything runs in Docker (PHP 8.4 with PCOV for coverage). You only need Docker and `make`.

```bash
git clone git@github.com:Mathis-Vincent/maintenance-bundle.git
cd maintenance-bundle
make install
```

| Command         | What it does                                                  |
|:----------------|:--------------------------------------------------------------|
| `make test`     | Run the PHPUnit test suite                                    |
| `make coverage` | Run the tests and write an HTML coverage report to `coverage/` |
| `make cs`       | Check the coding style (PHP-CS-Fixer, `@Symfony` rules)       |
| `make cs-fix`   | Fix the coding style                                          |
| `make psalm`    | Run the static analysis                                       |
| `make ci`       | Run every check, like the CI                                  |
| `make shell`    | Open a shell in the PHP container                             |

To try another PHP version locally: `docker compose build --build-arg PHP_VERSION=8.2`.

## Tests

- `tests/Unit`: isolated classes (token signing, state file).
- `tests/Functional`: a minimal Symfony app (`TestKernel`) with FrameworkBundle, SecurityBundle, TwigBundle and an in-memory user provider. Use it to test the real request flow (firewall, roles, cookies, 503 page) with `KernelBrowser` and `loginUser()`.

Every bug fix or new feature should come with a test.

## Pull requests

1. Fork the repository and create a branch from `develop`.
2. Make your change with its tests.
3. Run `make ci` and make sure everything is green.
4. Open a pull request against `develop` and describe what it changes and why.

The CI runs the test suite against PHP 8.2 to 8.5 and Symfony 6.4, 7.4 and 8.x. Code coverage is measured on `main` only: the summary is shown on the workflow run and the HTML report is attached as the `coverage-html` artifact.
