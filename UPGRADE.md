# Upgrade guide

## 2.0.0

### Configuration file structure changes

The 2.0 version introduces many configuration changes. Most of them will
gracefully fallback to older version until 3.0, but some have been removed
and will cause exceptions.

Rationale is that now, all top-level configuration options can be directly
set at the connection level, and we renamed those options to be more consistent
and more explicit about what they do.

Please read carefully the new sample configuration files:
 - For Symfony: [config/packages/db_tools.yaml](https://github.com/makinacorpus/DbToolsBundle/blob/main/config/packages/db_tools.yaml)
 - For standalone: [config/db_tools.standalone.yaml](https://github.com/makinacorpus/DbToolsBundle/blob/main/config/db_tools.standalone.sample.yaml)

And the [changelog](./changelog) file and fix your configuration accordingly.

The `backupper_binaries` and `backupper_options` as well as the `restorer_binaries`
and `restorer_options` options have been removed and will raise exception when
kept: older version allowed to configure backup and restore binaries on a per-vendor
basis, they are now configured on a per-connection basis without considering the
database vendor anymore. Please set those options for each connection instead.
