<?php
require_once __DIR__ . '/includes/bootstrap.php';

$orders = bot_fetch_orders($pdo);

$previewByOrder = [];
foreach ($orders as $o) {
    $tid = (int)$o['order_tid'];
    $previewByOrder[$tid] = bot_fetch_order_preview($pdo, $tid, 4);
}

include "includes/header.php";
?>
<div class="page">

    <div class="grid">
        <?php foreach ($orders as $o): ?>
            <?php
            $orderTid = (int)$o['order_tid'];
            $preview  = $previewByOrder[$orderTid] ?? [];
            $speciesCount = (int)$o['species_count'];
            $familyCount  = (int)$o['family_count'];
            $more = max(0, $speciesCount - count($preview));
            ?>
            <section class="card">
                <div class="cardHeader">
                    <div class="orderName"><?= h($o['order_name']) ?></div>
                    <div class="counts">
                        <span><?= $familyCount ?> families</span>
                        <span>•</span>
                        <span><?= $speciesCount ?> species</span>
                    </div>
                </div>

                <div class="cardBody">
                    <?php if (count($preview) === 0): ?>
                        <div class="loading">No species found.</div>
                    <?php else: ?>
                        <?php foreach ($preview as $b): ?>
                            <?php
                            $nid = (int)$b['nid'];
                            $thai = trim($b['thai_name'] ?? '');
                            $eng  = trim($b['english_name'] ?? '');
                            $sci  = trim($b['scientific_name'] ?? '');
                            $photos = (int)$b['photo_count'];
                            ?>
                            <div class="speciesRow">
                                <div class="speciesText">
                                    <a class="speciesLink" href="species.php?id=<?= $nid ?>">
                                        <?php if ($thai !== '' || $eng !== ''): ?>
                                            <div class="commonLine">
                                                <?php if ($thai !== ''): ?>
                                                    <span class="thai"><?= h($thai) ?></span>
                                                <?php endif; ?>

                                                <?php if ($thai !== '' && $eng !== ''): ?>
                                                    <span class="separator"> · </span>
                                                <?php endif; ?>

                                                <?php if ($eng !== ''): ?>
                                                    <span class="eng"><?= h($eng) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="sci"><?= h($sci) ?></div>
                                    </a>
                                </div>

                                <?php if ($photos > 0): ?>
                                    <div class="bubble"><?= $photos ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="cardFooter">
                    <div class="moreCount"><?= $more > 0 ? "+ {$more} more" : " " ?></div>
                    <button class="moreBtn"
                        type="button"
                        data-order-tid="<?= $orderTid ?>"
                        data-order-name="<?= h($o['order_name']) ?>">More →</button>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="sheet" role="document">
        <div class="sheetHeader">
            <div>
                <p class="sheetTitle" id="sheetTitle">Order</p>
                <p class="sheetSub" id="sheetSub">Loading…</p>
            </div>
            <button class="closeBtn" type="button" id="closeBtn">Close</button>
        </div>
        <div class="sheetBody">
            <div class="loading" id="loading">Loading…</div>
            <div id="families"></div>
        </div>
    </div>
</div>

<script>
    const overlay = document.getElementById('overlay');
    const closeBtn = document.getElementById('closeBtn');
    const sheetTitle = document.getElementById('sheetTitle');
    const sheetSub = document.getElementById('sheetSub');
    const loading = document.getElementById('loading');
    const familiesEl = document.getElementById('families');

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", "&#039;");
    }

    function openOverlay({
        orderTid,
        orderName
    }) {
        sheetTitle.textContent = orderName;
        sheetSub.textContent = 'Loading…';
        loading.hidden = false;
        familiesEl.innerHTML = '';

        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        history.pushState({
            overlay: true
        }, '', `#order-${orderTid}`);

        fetch(`order_full_list.php?order_tid=${encodeURIComponent(orderTid)}`)
            .then(r => r.json())
            .then(data => {
                sheetSub.textContent = `${data.family_count} families • ${data.species_count} species`;
                renderFamilies(data.families || []);
                loading.hidden = true;
            })
            .catch(() => {
                sheetSub.textContent = 'Failed to load.';
                loading.textContent = 'Sorry—could not load this list.';
                loading.hidden = false;
            });
    }

    function renderFamilies(families) {
        const parts = [];

        families.forEach(f => {
            const famName = escapeHtml(f.family_name || 'Unknown family');
            const species = f.species || [];

            const items = species.map(s => {

                const thaiLine = s.thai_name && s.thai_name.trim() !== '' ?
                    `<div class="thai">${escapeHtml(s.thai_name)}</div>` :
                    '';

                const engLine = s.english_name && s.english_name.trim() !== '' ?
                    `<div class="eng">${escapeHtml(s.english_name)}</div>` :
                    '';

                const sciLine = `
        <div class="sci">
          ${escapeHtml(s.scientific_name)}
        </div>
      `;

                const photoBubble = s.photo_count > 0 ?
                    `<div class="bubble">${Number(s.photo_count)}</div>` :
                    '';

                return `
        <li class="fullItem">
          <a class="speciesLink" href="species.php?id=${s.nid}">
            <div class="fullText">
              ${thaiLine}
              ${engLine}
              ${sciLine}
            </div>
          </a>
          ${photoBubble}
        </li>
      `;
            }).join('');

            parts.push(`
      <div class="famBlock">
        <div class="famHeader">
          <div class="famName">${famName}</div>
          <div class="famCount">${species.length} species</div>
        </div>
        <ul class="famList">
          ${items}
        </ul>
      </div>
    `);
        });

        familiesEl.innerHTML = parts.join('');
    }

    function closeOverlay() {
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (location.hash) history.back();
    }

    closeBtn.addEventListener('click', closeOverlay);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeOverlay();
    });
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.getAttribute('aria-hidden') === 'false') closeOverlay();
    });
    window.addEventListener('popstate', () => {
        if (overlay.getAttribute('aria-hidden') === 'false') {
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    });

    document.querySelectorAll('.moreBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            openOverlay({
                orderTid: btn.dataset.orderTid,
                orderName: btn.dataset.orderName
            });
        });
    });
</script>

<?php include "includes/footer.php"; ?>