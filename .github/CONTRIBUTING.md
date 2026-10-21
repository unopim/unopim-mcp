# How to contribute to UnoPim MCP

### **Security Vulnerabilities**

Please do **not** report security vulnerabilities through public GitHub issues. Refer to our [Security Policy](../SECURITY.md) for responsible disclosure instructions.

### **Bug Reporting**

1. Verify that the bug was not already reported by searching on GitHub in the [Issues section](https://github.com/unopim/unopim-mcp/issues).
If you're unable to find an open issue, [open a new one](https://github.com/unopim/unopim-mcp/issues/new?template=bug.yml).

2. Verify that the bug is in the MCP bridge and not in UnoPim core. Core bugs belong in the [UnoPim repository](https://github.com/unopim/unopim/issues).

3. Run through [Troubleshooting](../docs/troubleshooting.md) first — most connection and authentication problems are covered there.

#### **Did you fix a bug?**

1. Fork the [UnoPim MCP repository](https://github.com/unopim/unopim-mcp), make your changes and open a [Pull Request](https://help.github.com/articles/about-pull-requests/) against the `main` branch.
2. Separate each fix into a new branch in your repository and name it with the issue ID e.g. `issue-12`.
3. Use the [Conventional Commits](https://www.conventionalcommits.org/) format for your commit messages and reference the issue, e.g.
```fix(tools): accept comma-separated IN filters (#5)```
4. Please follow the pull request [template](https://github.com/unopim/unopim-mcp/blob/main/.github/PULL_REQUEST_TEMPLATE.md) as much as possible.

### **Did you create a new feature or enhancement?**

1. Open a [feature request](https://github.com/unopim/unopim-mcp/issues/new?template=feature_request.yml) first if one doesn't exist.
2. New tools must declare the UnoPim ACL permission they require and ship tests. See [Extending the Bridge](../docs/extending-mcp.md).
3. New user-facing strings use translation keys and ship in all 33 UnoPim locales.
4. Update [CHANGELOG.md](../CHANGELOG.md) under an `Unreleased` heading.

### **Before submitting a pull request**

Continuous integration runs these checks on every pull request against the latest UnoPim release — run them locally first from a UnoPim host with this package installed:

```bash
vendor/bin/pest         # PHP tests must pass on MySQL, MariaDB and PostgreSQL
vendor/bin/pint --test  # code style must pass (run `vendor/bin/pint` to auto-fix)
vendor/bin/phpstan analyse
php artisan unopim:translations:check
```
