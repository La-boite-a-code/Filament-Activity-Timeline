# Security Policy

## Supported versions

The latest minor release receives security fixes. Older releases are supported
on a best effort basis.

## Reporting a vulnerability

If you discover a security vulnerability, please email
`alexandre@laboiteacode.fr` rather than opening a public issue. You will
receive a response within a few business days. Please do not disclose the
issue publicly until a fix has been released.

## Scope and design notes

This package renders activity that has already been recorded by another system
(for example `spatie/laravel-activitylog`). It applies the following defaults:

- All values are escaped before rendering. Raw HTML is only produced through an
  API that is explicitly named as unsafe.
- Sensitive attributes are hidden by default and can be redacted or masked.
- The package never displays headers, tokens, passwords or arbitrary
  properties automatically.
- The package does not bypass Filament: it relies on the access control of the
  host page and never exposes activity that belongs to another record.

When you extend the package with custom sources, presentations or sentences,
keep untrusted values escaped and avoid the unsafe HTML API unless the content
is fully under your control.
