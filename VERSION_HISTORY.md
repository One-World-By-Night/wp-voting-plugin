# WP Voting Plugin - Version History

## Version 2.0.0 (Current - February 2026)

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
