/* Saudistore – live word-prefix suggestions, backed by available catalog stock. */
(function () {
 'use strict';
 var form = document.querySelector('.ws-header .ws-search');
 var input = form && form.querySelector('input[type="search"]');
 if (!input || !window.wordsiteSearch) return;
 var box = document.createElement('div'); box.className = 'ws-suggest'; box.hidden = true;
 var list = document.createElement('ul'); list.id = 'ws-search-suggestions'; list.setAttribute('role', 'listbox');
 var status = document.createElement('p'); status.className = 'ws-suggest__status'; status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
 box.appendChild(list); box.appendChild(status); form.appendChild(box);
 input.setAttribute('role', 'combobox'); input.setAttribute('aria-autocomplete', 'list'); input.setAttribute('aria-controls', list.id); input.setAttribute('aria-expanded', 'false'); input.setAttribute('autocomplete', 'off');
 var timer, controller, serial = 0, active = -1, items = [];
 function close() { box.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; }
 function clear() { items = []; list.replaceChildren(); close(); }
 function show() { box.hidden = false; input.setAttribute('aria-expanded', 'true'); }
 function select(index) {
  active = index;
  list.querySelectorAll('[role="option"]').forEach(function (el, i) { el.setAttribute('aria-selected', String(i === active)); });
  if (active >= 0) { input.setAttribute('aria-activedescendant', 'ws-suggestion-' + active); document.getElementById('ws-suggestion-' + active).scrollIntoView({ block: 'nearest' }); }
  else input.removeAttribute('aria-activedescendant');
 }
 async function request(term, id) {
  controller = new AbortController(); clear(); status.textContent = 'جارٍ البحث…'; show();
  try {
   var url = new URL(window.wordsiteSearch.endpoint); url.searchParams.set('term', term);
   var response = await fetch(url.href, { signal: controller.signal, credentials: 'same-origin', cache: 'no-store' });
   if (!response.ok) throw new Error('Search unavailable');
   var data = await response.json();
   if (id !== serial || input.value.trim() !== term) return;
   items = Array.isArray(data.suggestions) ? data.suggestions : [];
   list.replaceChildren();
   items.forEach(function (item, i) {
    var li = document.createElement('li'); li.setAttribute('role', 'presentation');
    var link = document.createElement('a'); link.href = item.url; link.id = 'ws-suggestion-' + i; link.setAttribute('role', 'option'); link.setAttribute('aria-selected', 'false'); link.tabIndex = -1;
    var img = document.createElement('img'); img.src = item.image; img.alt = ''; img.width = 44; img.height = 44;
    var copy = document.createElement('span'); var title = document.createElement('strong'); title.textContent = item.label; var note = document.createElement('small'); note.textContent = 'عرض جميع المنتجات المتوفرة'; copy.appendChild(title); copy.appendChild(note);
    link.appendChild(img); link.appendChild(copy); li.appendChild(link); list.appendChild(li);
   });
   status.textContent = items.length ? 'اختر اسماً لعرض جميع منتجاته' : 'لا توجد منتجات متوفرة بهذا الاسم';
   if (document.activeElement === input) show(); else close();
  } catch (error) {
   if (error.name === 'AbortError' || id !== serial) return;
   clear(); status.textContent = 'تعذر تحميل الاقتراحات. اضغط Enter للبحث.'; if (document.activeElement === input) show();
  }
 }
 input.addEventListener('input', function () {
  clearTimeout(timer); if (controller) controller.abort(); serial++; clear();
  var term = input.value.trim();
  if (Array.from(term).length < 2 || Array.from(term).length > 40) return;
  var id = serial; timer = setTimeout(function () { request(term, id); }, 220);
 });
 input.addEventListener('focus', function () { if (items.length) show(); });
 input.addEventListener('keydown', function (e) {
  if (e.key === 'Escape' && !box.hidden) { e.preventDefault(); e.stopPropagation(); close(); return; }
  if (box.hidden || !items.length) return;
  if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); select(active < 0 ? (e.key === 'ArrowDown' ? 0 : items.length - 1) : (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length); }
  else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); location.assign(items[active].url); }
 });
 document.addEventListener('pointerdown', function (e) { if (!form.contains(e.target)) close(); });
 form.addEventListener('submit', function () { close(); });
 document.addEventListener('focusin', function (e) { if (!form.contains(e.target)) close(); });
})();
