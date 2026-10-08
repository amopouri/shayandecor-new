# Empty catalog state

This release intentionally ships with **zero product rows** and **zero product-image rows** in `database.sql`.
The product schema and API remain intact.

For an already-installed database, run the authenticated `clear-products.php` once before production launch, then remove that utility from the server.
Do not add seed/demo products.
