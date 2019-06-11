# IPS Help Modules Backup & Restore
**Requires** IP-Symcon Version **4.3** or larger

These modules are used to quickly cache the configuration of an instance to restore it after it has been deleted.
Just delete an instance, then create new, then configure again can be **annoying**, especially when **developing**

**Module Backup & Restore** provides an easy way to simplify and help:
- Transfer a configuration from one to a new new instance of the same type.
- If required, create or duplicate any number of new instances of the same type.
- When deleting a module and reinstalling, cache the configuration
- and for everything that comes to mind


# Update 1.0 => 1.1
- Advanced to save up to 5 configurations

5 is defined by the private variable **$MaxBackups** = 5 in the module and can be increased, however, each storage is to be burdened by the size of the settings.json because the backup data are permanently stored in order to be still available at system reboot!

In order to avoid data garbage, it is recommended to remove unwanted backups after the restore.

To prevent accidental deletion of a backup in recovery mode, it is only possible to delete backups in "**backup**" mode.

Have fun ;-)