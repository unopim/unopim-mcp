# Security Policy

UnoPim MCP exposes catalog, settings and developer tools to AI agents, so
we treat security reports with the highest priority.

## Supported Versions

Only the latest minor release line receives security fixes. Upgrade to
the newest patch release before reporting.

| Version | PHP  | Laravel | UnoPim | Security Fixes |
| ------- | ---- | ------- | ------ | -------------- |
| 1.0.x   | 8.4.1+ | 13.x  | 3.0+   | ✅ Supported   |

---

## Reporting a Vulnerability

Report security issues privately. Do **not** open a public GitHub
issue, discussion or pull request.

**Email** — [support@webkul.com](mailto:support@webkul.com).

Please include:

- A description of the issue
- Steps to reproduce (and a PoC, if available)
- The transport involved (HTTP endpoint or stdio) and the affected tool(s)
- Impact assessment (CVSS 4.0 or 3.1 vector welcome)
- Affected version(s) of `unopim/mcp` and UnoPim
- A suggested fix, if any

We acknowledge receipt within **72 hours**.

---

## Scope

In scope:

- ACL bypass — a tool running without the UnoPim permission it requires
- Authentication or OAuth flaws on the HTTP transport
- Path traversal, command execution or SQL injection through the developer tools
- Data exposure through tool output, logs or error messages

Out of scope:

- Actions an authenticated admin with full access is already allowed to perform
- Developer tools enabled on a remote endpoint through `MCP_REMOTE_DEVELOPER_TOOLS=true`, used as documented
- Vulnerabilities in UnoPim core — report those per the [UnoPim security policy](https://github.com/unopim/unopim/security/policy)

---

## Security Process

1. **Acknowledgement** within 72 hours
2. **Triage** — reproduction and severity assessment
3. **Fix development** on `main`
4. **Patched release** tagged and published on Packagist
5. **GitHub Security Advisory** published with the reporter credited
   (when the reporter consents)

We aim to ship patches within **14 days** of acknowledgement for high
or critical severity, and **30 days** for medium severity.

---

## Thank You

Responsible disclosure keeps UnoPim and its users safe. Thank you for
taking the time to report issues to us privately.
