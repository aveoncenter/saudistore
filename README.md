# Saudistore — WooCommerce block theme (Arabic / RTL, dark)

Custom block theme for the saudidgi digital-subscriptions store.

## Install (WordPress Studio)
1. Clone this repo into your site's `wp-content\themes\` folder (folder name: `saudistore`).
2. WP Admin → Appearance → Themes → activate **Saudistore**.
3. Settings → General → **WhatsApp number** (digits only, e.g. 9665XXXXXXXX).
4. Pull new changes with GitHub Desktop → refresh the site.

## Notes
- Don't edit templates in the Site Editor: saved copies live in the database and override file updates.
  (If you did, Appearance → Editor → Templates → ⋮ → Reset.)
- Font: Cairo (SIL OFL), bundled in `assets/fonts`.

## Product page (v0.2.0)
`templates/single-product.html` + `inc/product-page.php` + `assets/css/product.css` + `assets/js/product.js`.

Per-product settings: **Products → Edit → Product data → General** (bottom):
- **عدد المبيعات المعروض** – number for "تم بيعه أكثر من N مرة" (empty = real sales, 0 sales = hidden).
- **نص الشروط** – if filled, the customer must tick it before buying.
- **اطلب إيميل الحساب** + **اسم الخدمة** – asks for the account e-mail (e.g. "ايميل لنكد إن"); saved on the order.

Package choice ("نوع الباقة") = a WooCommerce **variable product** with an attribute named `نوع الباقة` and one variation per package.
"منتجات قد تعجبك" uses the product's **Upsells** (Linked Products tab), otherwise best sellers.
Tip: WooCommerce → Settings → General → **Number of decimals = 0** for prices like 299.

## Online preview (WordPress Playground)

Open a fresh demo site (theme + WooCommerce + demo products) straight from this repo — nothing to install:

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/aveoncenter/saudistore/product-page/playground/blueprint.json

It always loads the latest code pushed to the `product-page` branch. Changes made inside the preview are temporary.
To change the branch or demo data, edit `playground/demo-setup.txt` and run `python3 playground/build.py <branch>`.
