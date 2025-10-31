# Security Policy

## Supported Versions

We provide security updates for the following versions:

| Version | Supported          |
|---------|--------------------|
| 1.x.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability, please **do not** open a public issue. Instead, send an email to *
*dev@neuralglit.ch** with the following information:

- Type of vulnerability
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit the issue

We will acknowledge receipt of your vulnerability report within 48 hours and provide a detailed response within 7 days.
We will keep you informed of the progress towards a fix and full announcement.

## Security Best Practices

When using this bundle, please ensure:

1. **Keep dependencies updated**: Regularly update Symfony and other dependencies
2. **Validate input**: Always validate and sanitize user input
3. **Use HTTPS**: Serve fonts over HTTPS in production
4. **Lock fonts in production**: Use the `gfonts:lock` command to download fonts locally for better security and privacy
5. **Review manifest files**: Regularly review and update locked font manifests

## Security Contact

For security issues, contact: **dev@neuralglit.ch**

