# Symfony 7.0 Upgrade Analysis for COG-GTM/-symfony-rest-sample-v7.0

## Executive Summary

This document provides a comprehensive analysis of dependencies and requirements for upgrading the Symfony Demo Application from version 6.0 to 7.0. The analysis identifies critical compatibility issues with abandoned third-party bundles and provides a complete migration strategy.

## Current vs Target Versions

### PHP Version Requirements
- **Current**: PHP ≥8.0.2
- **Target**: PHP ≥8.2.0 (Symfony 7.0 minimum requirement)
- **Recommendation**: Target PHP 8.2.0 for broader compatibility, with support for 8.3.x

### Symfony Components Upgrade Matrix

| Component | Current Version | Target Version | Status |
|-----------|----------------|----------------|---------|
| symfony/asset | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/console | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/dotenv | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/expression-language | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/form | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/framework-bundle | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/intl | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/mailer | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/runtime | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/security-bundle | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/string | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/translation | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/twig-bundle | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/validator | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/yaml | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/browser-kit | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/css-selector | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/debug-bundle | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/phpunit-bridge | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/stopwatch | ^6.0 | ^7.0 | ✅ Compatible |
| symfony/web-profiler-bundle | ^6.0 | ^7.0 | ✅ Compatible |

### Third-Party Dependencies Analysis

| Package | Current Version | Status | Action Required |
|---------|----------------|---------|-----------------|
| **dama/doctrine-test-bundle** | ^6.7 | ⚠️ Needs Update | Update to ^8.3 (supports Symfony 7.0) |
| **tgalopin/html-sanitizer-bundle** | ^1.4 | ❌ **ABANDONED** | Replace with symfony/html-sanitizer ^7.0 |
| **sensio/framework-extra-bundle** | ^6.2 | ❌ **ABANDONED** | Remove - functionality integrated into Symfony core |
| **symfony/webpack-encore-bundle** | ^1.13 → ^2.3 | ✅ Compatible | Update to ^2.3 for Symfony 7.0 support |

## Critical Migration Requirements

### 1. Compatible Bundle: symfony/webpack-encore-bundle

**Status**: ✅ **COMPATIBLE** with Symfony 7.0 in version ^2.3

**Action**: Update from ^1.13 to ^2.3 for Symfony 7.0 support.

**Changes Required**:
- Update `symfony/webpack-encore-bundle: ^2.3` in composer.json
- Version 2.3.0 (released 2025-08-05) supports symfony/asset ^5.4 || ^6.2 || ^7.0 || ^8.0

### 2. Abandoned Bundle: tgalopin/html-sanitizer-bundle

**Issue**: Package is abandoned and no longer maintained.

**Solution**: Replace with native Symfony HTML Sanitizer component.

**Changes Required**:
- Remove `tgalopin/html-sanitizer-bundle: ^1.4` from composer.json
- Add `symfony/html-sanitizer: ^7.0` to composer.json
- Update configuration in `config/packages/html_sanitizer.yaml`:
  - Change from `html_sanitizer:` to `framework: html_sanitizer:`
  - Update documentation references to Symfony docs

### 3. Abandoned Bundle: sensio/framework-extra-bundle

**Issue**: Package is abandoned. Core functionality has been integrated into Symfony.

**Solution**: Remove bundle and update imports to use native Symfony attributes.

**Changes Required**:
- Remove `sensio/framework-extra-bundle: ^6.2` from composer.json
- Update imports in controllers:
  - `Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted` → `Symfony\Component\Security\Http\Attribute\IsGranted`
  - `Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache` → `Symfony\Component\Cache\Annotation\Cache`
  - `Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter` → `Symfony\Bridge\Doctrine\Attribute\MapEntity`

**Files Affected**:
- `src/Controller/BlogController.php`
- `src/Controller/Admin/BlogController.php`
- `src/Controller/UserController.php`

### 4. Compatible Bundle: dama/doctrine-test-bundle

**Status**: Compatible with Symfony 7.0 in newer versions.

**Action**: Update from ^6.7 to ^8.3 for Symfony 7.0 support.

## Breaking Changes and Migration Notes

### 1. PHP Attributes vs Annotations
✅ **Good News**: The codebase already uses PHP attributes (introduced in PHP 8.0), which are the recommended approach for Symfony 7.0. No annotation-to-attribute migration needed.

### 2. Security Attribute Namespace Changes
The `IsGranted` attribute has moved from the SensioFrameworkExtraBundle to Symfony's core security component.

### 3. Cache Attribute Changes
Cache annotations are now part of Symfony's cache component rather than the extra bundle.

### 4. ParamConverter Replacement
The `ParamConverter` functionality is now handled by Symfony's `MapEntity` attribute from the Doctrine bridge.

## Upgrade Strategy Recommendation

### Sequential vs Direct Upgrade
**Recommendation**: **Direct upgrade** from Symfony 6.0 to 7.0

**Rationale**:
- Codebase already uses modern PHP 8.0+ features and attributes
- No legacy annotation usage to migrate
- Clean architecture with minimal technical debt
- Abandoned bundles need to be addressed regardless of upgrade path

### PHP Version Strategy
**Recommendation**: Target PHP 8.2.0 as minimum requirement

**Benefits**:
- Meets Symfony 7.0 minimum requirements
- Broader server compatibility than PHP 8.3
- Access to PHP 8.2 performance improvements and features
- Future-proof for Symfony 7.x lifecycle

## Potential Compatibility Issues

### 1. Service Configuration Changes
- HTML sanitizer service registration may need updates
- Bundle-specific service definitions may require adjustment

### 2. Twig Extensions
- Verify custom Twig extensions work with Symfony 7.0
- Check for any deprecated Twig features

### 3. Event System
- Review event listeners and subscribers for compatibility
- Check for any deprecated event classes

### 4. Form System
- Verify custom form types work with Symfony 7.0
- Check for deprecated form options or methods

## Testing Strategy

### Pre-Upgrade Validation
1. Run existing test suite to establish baseline
2. Document any failing tests before upgrade
3. Verify application boots and core functionality works

### Post-Upgrade Validation
1. `composer validate` - Verify composer.json syntax
2. `composer install --dry-run` - Test dependency resolution
3. `php bin/console about` - Verify application boots
4. Run full test suite
5. Manual testing of core user flows

### Rollback Plan
- Maintain backup of current composer.json and composer.lock
- Use git branches for safe upgrade testing
- Document any custom configurations that may need restoration

## Implementation Checklist

- [x] Update PHP version requirement to ≥8.2.0
- [x] Update platform configuration to PHP 8.2.0
- [x] Update all Symfony components from ^6.0 to ^7.0
- [x] Update Symfony requirement in extra section to 7.0.*
- [x] Remove sensio/framework-extra-bundle dependency
- [x] Replace tgalopin/html-sanitizer-bundle with symfony/html-sanitizer
- [x] Update dama/doctrine-test-bundle to ^8.3
- [x] Update controller imports for abandoned bundle replacements
- [x] Update HTML sanitizer configuration
- [ ] Run composer validate
- [ ] Test dependency resolution with composer install --dry-run
- [ ] Verify application boots with php bin/console about
- [ ] Run test suite
- [ ] Manual testing of core functionality

## Risk Assessment

### Low Risk
- Core Symfony component upgrades (well-tested upgrade path)
- PHP version upgrade (modern codebase already compatible)

### Medium Risk
- HTML sanitizer configuration changes (may affect content processing)
- Service container changes (may require configuration updates)

### High Risk
- Abandoned bundle replacements (requires code changes and testing)
- Potential breaking changes in third-party packages not yet tested

## Conclusion

The Symfony 7.0 upgrade is feasible with moderate effort required primarily for handling abandoned third-party bundles. The codebase is well-positioned for the upgrade due to its modern architecture and use of PHP attributes.

**Estimated Effort**: Medium complexity upgrade requiring careful testing of abandoned bundle replacements.

**Recommended Timeline**: 
1. Development and testing: 2-3 days
2. Staging validation: 1 day  
3. Production deployment: 1 day

**Success Criteria**:
- All dependencies resolve correctly
- Application boots without errors
- Full test suite passes
- Core functionality verified through manual testing
- Performance benchmarks maintained or improved
