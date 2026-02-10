# WP Voting Plugin - Version History

## Version 2.0.3 (Current - February 2026)

**PHP 7.4 compatibility fix for str_contains() usage.**

### Changes
- ✅ **PHP 7.4 compatibility**: Replaced `str_contains()` (PHP 8.0+) with `strpos()` for broader compatibility
- ✅ **Fixes both URL handling functions**: Test connection and permission check URL normalization now work on PHP 7.4+

### Bug Fix
The v2.0.2 release inadvertently used `str_contains()` which is only available in PHP 8.0+, causing silent failures on servers running PHP 7.4-7.9. This version restores compatibility with the plugin's minimum PHP requirement of 7.4.

---

## Version 2.0.2 (February 2026)

**Bug fixes for multisite settings and AccessSchema URL handling.**

### Changes
- ✅ **Settings page fix**: Register required settings sections for WordPress Settings API (required for multisite)
- ✅ **Flexible URL handling**: AccessSchema connection now accepts either base URL or full API URL
  - Base URL format: `https://example.com`
  - Full API URL format: `https://example.com/wp-json/access-schema/v1/`
  - Both formats automatically normalized for consistent API calls

### Technical Details
- Added `add_settings_section()` calls for `wpvp_general`, `wpvp_permissions`, and `wpvp_advanced` option groups
- Updated `accessSchema_client_get_remote_url()` to strip API path if present in URL
- Updated test connection AJAX handler to normalize URLs before building API endpoint

### Bug Fixes
- Fixed "wpvp_permissions options page is not in the allowed options list" error when saving settings on multisite
- Fixed test connection failure when full API URL provided instead of base URL

---

## Version 2.0.1 (February 2026)

**Multisite support enabled with per-site data isolation.**

### Changes
- ✅ **Multisite support**: Plugin now works on WordPress multisite/network installations
- ✅ **Per-site data isolation**: Each site in the network has its own votes, ballots, and results (isolated via `$wpdb->prefix`)
- ✅ **No shared data**: Data is completely isolated per site, no cross-site access

### Technical Details
The database layer already used `$wpdb->prefix` for all table names, which WordPress automatically makes site-specific in multisite environments:
- Site 1: `wp_wpvp_votes`, `wp_wpvp_ballots`, `wp_wpvp_results`
- Site 2: `wp_2_wpvp_votes`, `wp_2_wpvp_ballots`, `wp_2_wpvp_results`
- Site 3: `wp_3_wpvp_votes`, etc.

This ensures complete data isolation between sites with no additional code changes required.

### Migration Notes
If you were unable to use this plugin on multisite before, you can now activate it on individual sites within your network. Each site will have its own isolated voting system.

---

## Version 2.0.0 (February 2026)

**Complete rebuild of the voting plugin with improved architecture, security, and maintainability.**

### Location
- `/wp-voting-plugin/` (current directory)

### Major Changes
- **Complete rewrite**: Built from scratch with modern WordPress standards
- **25 custom PHP files**: Modular architecture with clear separation of concerns
- **Security hardened**: PHPCS clean (0 errors, 0 warnings), all WordPress security standards met
- **6 voting algorithms**: FPTP, RCV, STV, Condorcet, Disciplinary, Consent Agenda
- **AccessSchema integration**: Priority-chain permissions (accessSchema → WP caps)
- **Elementor widgets**: 3 widgets for vote lists, ballots, and results
- **Notification system**: wp_mail() with cron-based auto-open/close
- **Migration tool**: Idempotent v1→v2 data import (class-migration.php)

### Architecture
```
wp-voting-plugin.php                    # Bootstrap
├── includes/
│   ├── class-plugin.php                # Main controller
│   ├── class-activator.php             # Installation/upgrade
│   ├── class-database.php              # Schema management
│   ├── class-permissions.php           # AccessSchema → WP caps
│   ├── class-admin.php                 # Admin UI orchestration
│   ├── class-settings.php              # Settings page
│   ├── class-vote-list.php             # Vote list table
│   ├── class-vote-editor.php           # Vote creation/edit
│   ├── algorithms/                     # 6 algorithm implementations
│   ├── class-public.php                # Frontend orchestration
│   ├── class-ballot.php                # Ballot submission
│   ├── class-results-display.php       # Results rendering
│   ├── class-notifications.php         # Email + cron
│   ├── class-migration.php             # v1→v2 import
│   ├── class-elementor-widgets.php     # Widget registration
│   └── accessschema-client/            # AccessSchema client library (PHPCS clean)
├── templates/                          # Public templates
└── assets/                             # CSS/JS + Select2 vendor
```

### Code Quality
- ✅ PHPCS Clean: 0 errors, 0 warnings (WordPress Coding Standards)
- ✅ Security: Yoda conditions, wp_unslash, nonces, output escaping, wp_safe_redirect
- ✅ WordPress Standards: Follows all WordPress coding and plugin development standards
- ✅ Documentation: PHPDoc blocks for all public functions

### AccessSchema Client (Embedded)
- **Location**: `includes/accessschema-client/`
- **Status**: Production-ready, PHPCS clean
- **Version**: Cleaned and hardened (February 2026)
- **Features**: Remote/local/none modes, user role caching, admin interface

---

## Version 1.x (Legacy - Archived)

**Original implementation (~40% complete, multiple bugs, deprecated).**

### Archive
- **File**: `wp-voting-plugin-v1-legacy-20260209.tar.gz` (267KB)
- **Status**: Archived for reference only, not production-ready

### Known Issues (v1)
- Incomplete implementation (~40% of planned features)
- Multiple bugs in vote processing
- Inconsistent permission checks
- No PHPCS compliance
- Missing security hardening
- No migration path from v1 to v2 (v2 includes migration tool)

### Why v2 Was Built
The v1 codebase had accumulated too much technical debt and incomplete features. Rather than continue patching, a complete rebuild was undertaken to:
1. Implement all 6 voting algorithms correctly
2. Add proper security hardening
3. Integrate with AccessSchema properly
4. Meet WordPress plugin directory standards
5. Provide a migration path from v1

---

## Migration Guide (v1 → v2)

If you have v1 data:
1. Install v2 (this version)
2. Activate the plugin
3. Go to **Voting > Settings > Migration** tab
4. Click **"Run Migration"**
5. The migration is idempotent (safe to run multiple times)

The migration tool (`class-migration.php`) will:
- Import all v1 votes with settings preserved
- Map v1 algorithms to v2 equivalents
- Convert ballot data to v2 format
- Preserve all timestamps and user associations

---

## Support & Development

- **Repository**: https://github.com/One-World-By-Night/wp-voting-plugin
- **Author**: One World By Night
- **License**: GPL-2.0-or-later
- **Requires**: WordPress 5.0+, PHP 7.4+
