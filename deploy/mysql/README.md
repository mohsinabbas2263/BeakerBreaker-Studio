# MySQL encryption at rest (`SR-06`)

The three files here turn on InnoDB encryption for every table, the redo and undo logs
and the binary log, with the keys held by MySQL 8.4's `component_keyring_file`.

| File | Mounted at (mysql:8.4 image) | What it does |
|---|---|---|
| `mysqld.my` | `/usr/sbin/mysqld.my` | Global manifest: loads the keyring component at start-up |
| `component_keyring_file.cnf` | `/usr/lib64/mysql/plugin/` | Where the keyring file lives (its own volume) |
| `encryption.cnf` | `/etc/mysql/conf.d/` | `default_table_encryption=ON` and the log encryption |

## Measured on 9 Nov 2026

On MySQL 8.4.9 (Windows, same component and settings, port 3308):

- `performance_schema.keyring_component_status` showed the component **Active**.
- `php artisan migrate:fresh --seed` ran clean; **no** tablespace of the database had
  `ENCRYPTION='N'` in `information_schema.INNODB_TABLESPACES`.
- The raw `users.ibd` file contained the seeded email address **0** times; the same file
  from an unencrypted server contained it **2** times. The app still read all 6 users.

Not yet run inside the Docker image; check the same three things on the server
(docs/deploy.md, step 5).

## The keyring is the key to the data

If the keyring volume is lost, the database cannot be opened again, backups of the data
volume included. `deploy/backup.sh` copies the keyring next to every dump, and the
dumps themselves are plain SQL (encrypted with `BACKUP_PASSPHRASE`), so a restore into
a new server never needs the old keyring.
