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

## Category menu (v0.2.8)

The header's ☰ menu opens a right-side category drawer (full width on smaller screens), matching the supplied reference: a centered "القائمة الرئيسية" title, a red close button, and five plain rows with thumbnails on the right.
Extra headings, subtitles, card decoration, and quick links are omitted from this drawer.
The theme creates these WooCommerce categories once, including when an already active theme is updated.
Matching category names/slugs are reused; existing products are never reassigned.

- اشتراكات الذكاء الاصطناعي
- سناب بلس وتويتر بريميوم الرسمي
- اشتراكات برامج الأفلام والمسلسلات
- تطبيقات المونتاج والتصاميم
- اشتراكات لينكد إن LinkedIn

Manage categories under **Products → Categories**, and assign products from each product's **Product categories** panel.
Category IDs are retained so renaming a category or its slug does not break the menu.
The demo assigns AI, Canva, Netflix, and LinkedIn products to the corresponding categories; Snapchat/X is empty until products are added.
The `wordsite_store_menu_categories` filter customizes the defaults. Core navigation continues to handle keyboard focus, Escape, and the close button; clicking the dimmed desktop backdrop also closes the drawer.
The supplied screenshot thumbnails are bundled losslessly at their original 75×75 size. Upload an image under **Products → Categories → Edit → Thumbnail** to override a category's bundled image.

## Homepage product shelves (v0.2.15)

The previous best-sellers and new-arrivals blocks are replaced by five live WooCommerce shelves, immediately below the unchanged category carousel:
**الأكثر طلبًا**, **الذكاء الاصطناعي**, **اشتراكات لينكدإن**, **التصميم والمونتاج**, **الأفلام والمسلسلات**.
Each shelf uses compact cyan-outlined cards, actual product images and prices, add-to-cart/plan selection, and quick view. Products are not copied from the reference screenshots.
The first shelf ranks up to 12 available catalog products by WooCommerce `total_sales` (ID descending breaks ties); six are visible on desktop and one slides left every **three seconds** when there are more than six. Tablet shows four/three and mobile shows two.
The four category shelves use the saved category IDs, rank up to 12 products by sales, and offer manual arrows, swiping, and keyboard navigation when needed. Empty categories show a clear message instead of unrelated products. Short rows are right-aligned and are never padded with duplicates.
Sold-out products receive a stock stamp and disabled purchase control where WooCommerce allows them to remain visible; quick view and product details remain available. Store-level hide-out-of-stock settings are respected. Catalog-hidden, draft, private, and password-protected products are excluded.
Autoplay pauses on hover, focus, open quick view, hidden tabs, and off-screen shelves. Reduced-motion disables autoplay and sliding animation. Without JavaScript the rows remain horizontally scrollable.
The existing benefits and contact panels remain below the new shelves.

## Homepage categories (v0.2.14)

A six-card category carousel sits between the payment strip and the product shelves.
The heading reads **تصفح أقسامنا بكل سهولة**. Full illustrated cards preserve the supplied reference styling, including the service-icon rows, without extra text or call-to-action rows.
It uses artwork cropped from the supplied references and the six reference categories:
AI, learning/skills, design/creativity, work/professional development, gift cards, and apps/subscriptions.
Desktop hover gently raises each card by 6px and enlarges it by 2%, with a subtle brightness/shadow effect and a 260ms return transition. Keyboard focus receives the same lift; touch devices avoid sticky hover, and reduced-motion users get no lift/zoom.
The five existing drawer categories are unchanged. New category terms are created once without renaming existing terms or reassigning live products.
The demo additionally assigns LinkedIn to work/professional development and Spotify to apps/subscriptions; new categories may otherwise be empty until products are assigned.

Desktop shows five cards, tablet three, and mobile two. A smooth one-card **physical leftward** step starts every two seconds and loops seamlessly.
Autoplay pauses on hover, keyboard focus, off-screen sections, or hidden tabs, with swipe and keyboard navigation. The visible arrows, counter, and pause-control row have been removed.
Reduced-motion users get manual navigation only. Without JavaScript the category strip remains scrollable.
Use `wordsite_home_categories` to adjust the homepage category definitions; links resolve from saved WooCommerce term IDs.

## Live product search (v0.2.11)

The header search suggests names from words beginning with the typed text in published, in-stock, searchable product titles (minimum two characters).
For example, `net` can suggest Netflix and Netlify **only if matching available products actually exist**.
Suggestions are grouped by name, not individual plans. A name opens all matching available products, even when there is only one result.
Stock is checked on every suggestion request; sold-out, backordered, draft, password-protected, and search-hidden products are excluded.

The results page uses the existing product cards, quick view, and pagination, followed by available products from the matching categories.
The five configured categories take priority over a broad parent category; matching search products are not repeated in recommendations.
No customer-view tracking is implied or required.

Keep service spellings in product titles and assign the correct **Product categories** to support matching and recommendations.
The demo includes two Netflix plans plus Shahid in the movies/series category to demonstrate the flow.

## Online preview (WordPress Playground)

Open a fresh demo site (theme + WooCommerce + demo products) straight from this repo — nothing to install:

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/aveoncenter/saudistore/product-page/playground/blueprint.json

It always loads the latest code pushed to the `product-page` branch. Changes made inside the preview are temporary.
To change the branch or demo data, edit `playground/demo-setup.txt` and run `python3 playground/build.py <branch>`.
