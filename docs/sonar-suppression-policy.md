# Sonar Suppression Policy

This document outlines the policy for suppressing SonarQube findings in the Symfony Demo Application.

## General Principles

1. **Prefer fixing over suppressing** - Always attempt to fix the underlying issue before considering suppression.
2. **Tune rule profiles over suppressing** - If a rule generates too many false positives, request a profile adjustment rather than suppressing individual findings.
3. **Document all suppressions** - Every suppression must include a justification explaining why it cannot be fixed.

## Allowed Suppression Mechanisms

### PHP Annotations

Use `@SuppressWarnings` annotations with a trailing comment explaining the rationale:

```php
/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * Rationale: This method handles multiple edge cases that cannot be easily refactored
 * without reducing readability. See issue #123 for discussion.
 */
public function complexMethod(): void
{
    // ...
}
```

### Single-Line Suppressions

For single-line suppressions, use inline comments with justification:

```php
$result = $this->legacyMethod(); // NOSONAR - Legacy API requirement, tracked in issue #456
```

## Forbidden Practices

The following suppression practices are **not allowed**:

1. **File-wide suppressions** - Suppressing all findings in an entire file without individual justification
2. **Directory-wide exclusions** - Excluding entire directories to hide real issues (except for generated code)
3. **Blanket rule disabling** - Disabling entire rule categories to make the Quality Gate pass
4. **Suppressions without justification** - Any suppression that lacks a clear explanation

## Approval Process

1. **Minor suppressions** (single line, clear false positive) - Can be added with PR review approval
2. **Major suppressions** (multiple lines, architectural decisions) - Require team lead approval and issue tracking
3. **Profile changes** (rule adjustments) - Require security team review

## Legitimate Exclusions

The following are pre-approved exclusions in `sonar-project.properties`:

- `**/vendor/**` - Third-party dependencies
- `**/var/**` - Cache and log files
- `**/node_modules/**` - Node.js dependencies
- `**/public/build/**` - Compiled frontend assets

## Review and Audit

Suppressions should be reviewed periodically (quarterly) to determine if:

- The underlying issue has been fixed
- The suppression is still necessary
- Better alternatives exist

## References

- [SonarQube Documentation](https://docs.sonarqube.org/)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
