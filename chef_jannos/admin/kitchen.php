<?php
require '../config.php';
need_role('kitchen');
head('Kitchen Queue', true);
?>
<p class="text-muted">Live queue (FR-03 to FR-05) — refreshes every 5 seconds. Oldest
orders first.</p>
<div id="board" class="kgrid">
</div>
<script>
    const API = '../api/index.php',
        NEXT = {
            pending: ['preparing', 'Start preparing'],
            preparing: ['served', 'Mark served'],
            served: ['completed', 'Mark completed'],
        },
        COL = { pending: 'secondary', preparing: 'warning', served: 'info' };
    const esc = (s) =>
        String(s ?? '').replace(
            /[&<>"']/g,
            (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
        );
    const card = (o) =>
        `<div class="kcard st-${o.status}">` +
        `<div class="d-flex justify-content-between"><span class="ordno" style="font-size:2rem">${esc(o.label)}</span>` +
        `<span class="badge bg-${COL[o.status]}">${esc(o.status)}</span></div>` +
        `<small class="text-muted">${esc(o.time)} · ${esc(o.who)} · ${esc(o.type)} ${esc(o.table)} · ${esc(o.source)}</small>` +
        `<ul class="my-2 ps-3">${o.items.map((i) => `<li><b>${i.qty}×</b> ${esc(i.name)}</li>`).join('')}</ul>` +
        `${o.special ? `<div class="small">📝 ${esc(o.special)}</div>` : ''}` +
        `${o.allergies ? `<div class="allergy">⚠ ALLERGY: ${esc(o.allergies)}</div>` : ''}` +
        `<button class="btn btn-o btn-sm mt-2" onclick="setS(${o.id},'${NEXT[o.status][0]}')">${NEXT[o.status][1]}</button></div>`;
    async function load() {
        try {
            const d = await (await fetch(API + '?r=queue')).json();
            document.getElementById('board').innerHTML = d.length
                ? d.map(card).join('')
                : '<p class="text-muted">No active orders. 🎉</p>';
        } catch (e) {}
    }
    async function setS(id, s) {
        await fetch(API + '?r=status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&status=' + s,
        });
        load();
    }
    load();
    setInterval(load, 5000);
</script>
<?php
foot();
