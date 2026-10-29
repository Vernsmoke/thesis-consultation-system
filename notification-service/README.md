# Controlled notification receiver

Standalone PHP/PDO SQLite test dependency for Week 13. It accepts minimal notifications and issues idempotent receipts; it does not send email/SMS. See [contract, private setup, fault modes and limitations](../docs/DELIVERY.md).

Run from this folder, only after protected configuration is prepared and port 8090 is free:

```powershell
php -S 127.0.0.1:8090 -t public public/index.php
```

Only `public/` is served. `.env` and `storage/receiver.sqlite` stay ignored and private. Do not delete/reset the receipt ledger to repeat evidence. Authentication also protects `/test/control`; do not expose this classroom fixture service publicly. `/health` reports liveness only. No Composer installation or separate SQLite server is needed; the installed PHP PDO SQLite extension is used.
