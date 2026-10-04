# Sales Reports Dashboard

[Versión en español](README.md)

Admin dashboard for a wholesale distributor, built in **plain PHP** (compatible with PHP 5.6 to 8.x), **SQL**, **jQuery + Ajax**, **Bootstrap 5** and **Chart.js**. The interface is in Spanish.

![Dashboard](docs/capturas/1-panel.png)

## Features
- Period KPIs: revenue, number of sales, average ticket and units, each compared against the previous period of the same length.
- Monthly revenue and sales count chart with two axes.
- Breakdown by category and top 5 products.
- Salesperson ranking with share of total.
- Filters by date, salesperson and category, with quick ranges (1, 3 and 12 months). Everything is recalculated via Ajax without reloading.
- CSV export of the filtered detail, ready to open in Excel.

## Data model
```
vendedores 1───N ventas 1───N venta_items N───1 productos N───1 categorias
(salespeople)    (sales)      (sale lines)      (products)      (categories)
```
The price is stored on each sale line (`venta_items.precio_unitario`), so historical reports don't change when the price list is updated.

## Notable queries (`includes/reportes.php`)
- **Shared base:** one `FROM` with 4 `JOIN`s and filters built with prepared statements. Every report reuses it.
- **By month:** `GROUP BY` on the formatted date. It uses `DATE_FORMAT` on MySQL and `strftime` on SQLite, since each engine has its own function.
- **KPIs:** `SUM(cantidad * precio_unitario)`, `COUNT(DISTINCT v.id)` (a sale has several lines) and `COUNT(DISTINCT cliente)`.
- **Ranking:** `GROUP BY` + `HAVING` + `ORDER BY`. The share is computed over the total with the same filters.
- **Export:** walks the result row by row with `fetch()` and `fputcsv` to `php://output`, without loading everything into memory. Adds a UTF-8 BOM and uses `;` as separator for Spanish-locale Excel.

## Documented test cases
| # | Case | Expected result |
|---|---|---|
| 1 | Open the dashboard with no filters | Shows the last 12 months and compares them with the previous 12. |
| 2 | Pick "Bebidas" and the 3M range | KPIs, charts and ranking are recalculated; the export link carries the same filters. |
| 3 | Invalid dates in the URL (`desde=xx`) | Ignored; the default range is used. |
| 4 | Period with no sales | KPIs at 0 and "Sin ventas en el período" in the ranking. |
| 5 | Export CSV | Downloads `ventas_<from>_a_<to>.csv` with dd/mm/yyyy dates and comma decimals. |

## Installation
**Local (demo mode):** `php -S localhost:8000`. It creates `data/demo.sqlite` with 24 months of generated sales (always the same, thanks to a fixed seed).

**Server with MySQL:**
1. Run `sql/schema.sql`.
2. Generate the data with `php sql/generar_datos.php > sql/datos.sql` and run it.
3. Fill in `includes/config.php` and upload the files.

## Screenshots
| Filtered by category | Mobile |
|---|---|
| ![](docs/capturas/2-filtrado.png) | ![](docs/capturas/3-celular.png) |
