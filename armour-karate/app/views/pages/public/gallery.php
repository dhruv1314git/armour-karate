<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Photo Gallery — Public Page
 * app/views/pages/public/gallery.php
 *
 * Faithfully recreates the original Next.js Gallery page:
 *  - Hero banner with badge
 *  - Category filter tabs (vanilla JS, no React)
 *  - Responsive image grid (4 cols → 3 → 2 → 1)
 *  - Lightbox modal with keyboard Escape support
 *  - Professional empty state when no visible images exist
 *
 * Data source: `gallery_images` table WHERE is_visible = 1
 * No fake/demo images are displayed.
 */

declare(strict_types=1);

$pageTitle       = 'Dojo Photo Gallery | Armour Karate Academy';
$metaDescription = 'Explore dynamic photos of Goju-Ryu karate training, national championship victories, black belt promotions, and outdoor summer camps by Armour Martial Arts Gujju Karate Association.';

// ─── Database Query ───────────────────────────────────────────────────────────
$pdo    = db();
$photos = [];

try {
    $stmt = $pdo->query(
        "SELECT id, image_url, title, description, category
           FROM gallery_images
          WHERE is_visible = 1
          ORDER BY created_at DESC"
    );
    $photos = $stmt->fetchAll();
} catch (PDOException $e) {
    appLog('ERROR', 'Gallery page DB error: ' . $e->getMessage());
    $photos = [];
}

// ─── Category definitions (matches original GalleryViewer) ────────────────────
$categories = [
    'ALL'            => 'All Photos',
    'TRAINING'       => 'Dojo Training',
    'TOURNAMENTS'    => 'Tournaments',
    'BELT_EXAMS'     => 'Belt Exams',
    'AWARDS'         => 'Awards & Honors',
    'CAMPS'          => 'Summer Camps',
    'BLACK_BELT'     => 'Black Belts',
    'ACADEMY_EVENTS' => 'Academy Events',
];

// ─── Layout ───────────────────────────────────────────────────────────────────
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="page-section" style="background:#0A0A0A; color:#fff; padding: 5rem 0 4rem;">
    <div class="container" style="max-width:1280px;">

        <!-- ── Hero Banner ────────────────────────────────────────────────── -->
        <div class="text-center mb-5 pb-2" style="max-width:760px; margin-left:auto; margin-right:auto;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                 style="background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171; font-size:0.7rem; font-weight:800; letter-spacing:0.12em; text-transform:uppercase;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 3.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>
                    <path d="M.5 1C.224 1 0 1.224 0 1.5v13c0 .276.224.5.5.5H15.5c.276 0 .5-.224.5-.5v-13c0-.276-.224-.5-.5-.5H.5zM1 2h14v11.5l-3.213-6.817A.5.5 0 0 0 11.33 6.5l-3.166 6.332L6.874 9.75a.5.5 0 0 0-.837.01L2 14.5V2z"/>
                </svg>
                Visual Dojo Chronicle
            </div>
            <h1 class="fw-black text-uppercase text-white mb-3"
                style="font-size: clamp(2.2rem, 5vw, 3.6rem); letter-spacing:-0.03em; line-height:1.05;">
                Photo <span style="color:#DC2626;">Gallery</span>
            </h1>
            <p style="color:#d4d4d4; font-size:0.925rem; line-height:1.7;">
                Moments of intense martial focus, championship victories, and the enduring bond of dojo brotherhood.
            </p>
        </div>

        <?php if (empty($photos)): ?>
            <!-- ── Empty State ─────────────────────────────────────────────── -->
            <div class="rounded-4 p-5 text-center"
                 style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a;">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="#DC2626" viewBox="0 0 16 16" opacity="0.4" aria-hidden="true">
                        <path d="M.5 1C.224 1 0 1.224 0 1.5v13c0 .276.224.5.5.5H15.5c.276 0 .5-.224.5-.5v-13c0-.276-.224-.5-.5-.5H.5zM1 2h14v11.5l-3.213-6.817A.5.5 0 0 0 11.33 6.5l-3.166 6.332L6.874 9.75a.5.5 0 0 0-.837.01L2 14.5V2z"/>
                    </svg>
                </div>
                <p class="fw-bold text-uppercase mb-1" style="color:#DC2626; font-size:0.7rem; letter-spacing:0.12em;">
                    Gallery Under Curation
                </p>
                <h3 class="fw-black text-white text-uppercase mb-2" style="font-size:1.15rem;">
                    Dojo Chronicle Being Prepared
                </h3>
                <p style="color:#a3a3a3; font-size:0.82rem; max-width:480px; margin:0 auto 1.5rem;">
                    Our media team is curating and verifying photographs from championships, belt examinations, and training sessions. The gallery will be published soon.
                </p>
                <a href="<?= BASE_PATH ?>/contact" class="btn btn-sm btn-danger px-4 fw-bold text-uppercase"
                   style="letter-spacing:0.08em; background:#DC2626; border:none;">
                    Enquire With Academy
                </a>
            </div>

        <?php else: ?>
            <!-- ── Category Filter Tabs ────────────────────────────────────── -->
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-5" id="galleryFilterBar" role="tablist" aria-label="Gallery categories">
                <?php foreach ($categories as $catValue => $catLabel): ?>
                    <button
                        type="button"
                        class="gallery-filter-btn<?= $catValue === 'ALL' ? ' active' : '' ?>"
                        data-category="<?= escAttr($catValue) ?>"
                        role="tab"
                        aria-selected="<?= $catValue === 'ALL' ? 'true' : 'false' ?>"
                    >
                        <?= esc($catLabel) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- ── Gallery Grid ────────────────────────────────────────────── -->
            <div class="row g-4" id="galleryGrid">
                <?php foreach ($photos as $photo): ?>
                    <?php
                    $catVal      = $photo['category'] ?? 'TRAINING';
                    $catDisplay  = str_replace('_', ' ', $catVal);
                    $imageUrl    = !empty($photo['image_url']) ? $photo['image_url'] : '';
                    $title       = !empty($photo['title'])     ? $photo['title']     : 'Dojo Photograph';
                    $description = !empty($photo['description']) ? $photo['description'] : '';
                    ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 gallery-item"
                         data-category="<?= escAttr($catVal) ?>">
                        <div class="gallery-card rounded-4 overflow-hidden position-relative"
                             style="height:240px; background:#111; border:1px solid #2a2a2a; cursor:pointer;"
                             role="button"
                             tabindex="0"
                             aria-label="View <?= escAttr($title) ?>"
                             data-img="<?= escAttr($imageUrl) ?>"
                             data-title="<?= escAttr($title) ?>"
                             data-desc="<?= escAttr($description) ?>"
                             data-cat="<?= escAttr($catDisplay) ?>"
                             onclick="openLightbox(this)"
                             onkeydown="if(event.key==='Enter'||event.key===' '){openLightbox(this);}">

                            <?php if (!empty($imageUrl)): ?>
                                <img src="<?= escAttr($imageUrl) ?>"
                                     alt="<?= escAttr($title) ?>"
                                     class="gallery-card-img w-100 h-100"
                                     style="object-fit:cover; transition:transform 0.5s ease;"
                                     loading="lazy">
                            <?php else: ?>
                                <!-- No image: gradient placeholder -->
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center p-3 text-center"
                                     style="background: linear-gradient(135deg, #1a1a1a 0%, #3b0a0a 100%);">
                                    <span style="font-size:0.65rem; font-weight:800; color:#f87171; text-transform:uppercase; letter-spacing:0.12em;">
                                        <?= esc($catDisplay) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Gradient Overlay -->
                            <div class="gallery-overlay position-absolute inset-0 w-100 h-100"
                                 style="top:0; left:0; background:linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 50%, transparent 100%); opacity:0.8; transition:opacity 0.3s;"></div>

                            <!-- Zoom Icon -->
                            <div class="position-absolute top-0 end-0 m-2 p-1 rounded-circle"
                                 style="background:rgba(0,0,0,0.5); opacity:0; transition:opacity 0.3s; pointer-events:none;"
                                 aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#fff" viewBox="0 0 16 16">
                                    <path d="M6.5 12.5a6 6 0 1 0 0-12 6 6 0 0 0 0 12zm4.354-9.354L15 7.293l-4.146-4.147a.5.5 0 0 0-.708.708z"/>
                                    <path fill-rule="evenodd" d="M6.5 0a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13zm6.354 11.354-3-3a.5.5 0 0 0-.708.708l3 3a.5.5 0 0 0 .708-.708z"/>
                                    <path d="M6.5 12.5a6 6 0 1 0 0-12 6 6 0 0 0 0 12zm.5-8.5a.5.5 0 0 1 .5.5v1.5H9a.5.5 0 0 1 0 1H7.5V7a.5.5 0 0 1 .5-.5z"/>
                                </svg>
                            </div>

                            <!-- Caption -->
                            <div class="position-absolute bottom-0 start-0 end-0 p-3">
                                <span style="display:block; font-size:0.6rem; font-weight:800; color:#DC2626; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:2px;">
                                    <?= esc($catDisplay) ?>
                                </span>
                                <h4 class="gallery-card-title mb-0"
                                    style="font-size:0.82rem; font-weight:700; color:#fff; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; transition:color 0.2s;">
                                    <?= esc($title) ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ── No-results message (shown by JS when filter returns 0) ── -->
            <div id="galleryNoResults" class="py-5 text-center rounded-4 mt-4" style="background:rgba(28,28,30,0.4); border:1px solid #222; display:none;">
                <p style="color:#737373; font-size:0.82rem;">No photos found in this category yet.</p>
            </div>

        <?php endif; ?>

    </div><!-- /.container -->
</div>

<!-- ── Lightbox Modal ─────────────────────────────────────────────────────── -->
<div id="galleryLightbox"
     role="dialog"
     aria-modal="true"
     aria-label="Photo viewer"
     tabindex="-1"
     style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.9); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:1rem;">

    <div style="max-width:900px; width:100%; background:#0a0a0a; border:1px solid #2a2a2a; border-radius:1.5rem; overflow:hidden; position:relative; box-shadow:0 40px 80px rgba(0,0,0,0.7);"
         role="document"
         onclick="event.stopPropagation()">

        <!-- Close Button -->
        <button type="button"
                id="lightboxClose"
                onclick="closeLightbox()"
                aria-label="Close photo viewer"
                style="position:absolute; top:0.75rem; right:0.75rem; z-index:10; width:36px; height:36px; background:rgba(0,0,0,0.6); border:none; border-radius:50%; color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:background 0.2s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
            </svg>
        </button>

        <!-- Image Area -->
        <div id="lightboxImageWrap"
             style="width:100%; height:clamp(240px, 60vh, 520px); background:#000; display:flex; align-items:center; justify-content:center; overflow:hidden;">
            <img id="lightboxImg"
                 src=""
                 alt=""
                 style="max-width:100%; max-height:100%; object-fit:contain;">
            <div id="lightboxPlaceholder"
                 style="display:none; font-size:0.7rem; font-weight:800; color:#f87171; text-transform:uppercase; letter-spacing:0.12em;"></div>
        </div>

        <!-- Caption -->
        <div style="padding:1.25rem 1.5rem; background:#111; border-top:1px solid #222;">
            <span id="lightboxCat" style="font-size:0.6rem; font-weight:800; color:#DC2626; text-transform:uppercase; letter-spacing:0.1em; display:block; margin-bottom:4px;"></span>
            <h3 id="lightboxTitle" style="font-size:1.1rem; font-weight:700; color:#fff; margin:0 0 4px 0;"></h3>
            <p id="lightboxDesc" style="font-size:0.78rem; color:#a3a3a3; margin:0;"></p>
        </div>
    </div>
</div>

<style>
/* ── Gallery Filter Buttons ────────────────────────────────────────────── */
.gallery-filter-btn {
    padding: 0.45rem 1rem;
    border-radius: 0.65rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border: 1px solid #2a2a2a;
    background: #111;
    color: #a3a3a3;
    cursor: pointer;
    transition: all 0.2s ease;
}
.gallery-filter-btn:hover {
    background: #1f1f1f;
    color: #fff;
}
.gallery-filter-btn.active {
    background: #DC2626;
    color: #fff;
    border-color: #DC2626;
    box-shadow: 0 4px 16px rgba(220,38,38,0.3);
}

/* ── Gallery Card Hover ────────────────────────────────────────────────── */
.gallery-card:hover {
    border-color: rgba(220,38,38,0.5) !important;
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(127,29,29,0.25);
}
.gallery-card:hover .gallery-card-img {
    transform: scale(1.06);
}
.gallery-card:hover .gallery-overlay {
    opacity: 0.95 !important;
}
.gallery-card:hover > div[aria-hidden] {
    opacity: 1 !important;
}
.gallery-card:hover .gallery-card-title {
    color: #fca5a5 !important;
}

/* ── Lightbox ──────────────────────────────────────────────────────────── */
#galleryLightbox.active {
    display: flex !important;
}
#lightboxClose:hover {
    background: #DC2626 !important;
}
</style>

<script>
(function () {
    'use strict';

    // ── Category Filtering ────────────────────────────────────────────────
    const filterBtns    = document.querySelectorAll('.gallery-filter-btn');
    const galleryItems  = document.querySelectorAll('.gallery-item');
    const noResults     = document.getElementById('galleryNoResults');

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const cat = btn.dataset.category;

            // Update active state
            filterBtns.forEach(function (b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            // Show/hide items
            let visibleCount = 0;
            galleryItems.forEach(function (item) {
                const match = (cat === 'ALL') || (item.dataset.category === cat);
                item.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    });

    // ── Lightbox ──────────────────────────────────────────────────────────
    const lightbox    = document.getElementById('galleryLightbox');
    const lbImg       = document.getElementById('lightboxImg');
    const lbHolder    = document.getElementById('lightboxPlaceholder');
    const lbCat       = document.getElementById('lightboxCat');
    const lbTitle     = document.getElementById('lightboxTitle');
    const lbDesc      = document.getElementById('lightboxDesc');

    window.openLightbox = function (el) {
        const imgSrc = el.dataset.img  || '';
        const title  = el.dataset.title || 'Dojo Photograph';
        const desc   = el.dataset.desc  || '';
        const cat    = el.dataset.cat   || '';

        if (imgSrc) {
            lbImg.src = imgSrc;
            lbImg.alt = title;
            lbImg.style.display    = '';
            lbHolder.style.display = 'none';
        } else {
            lbImg.src              = '';
            lbImg.style.display    = 'none';
            lbHolder.textContent   = cat;
            lbHolder.style.display = '';
        }

        lbCat.textContent   = cat;
        lbTitle.textContent = title;
        lbDesc.textContent  = desc;

        lightbox.classList.add('active');
        lightbox.setAttribute('aria-hidden', 'false');
        document.getElementById('lightboxClose').focus();
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function () {
        lightbox.classList.remove('active');
        lightbox.setAttribute('aria-hidden', 'true');
        lbImg.src = '';
        document.body.style.overflow = '';
    };

    // Click outside to close
    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) { closeLightbox(); }
    });

    // Keyboard: Escape closes
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            closeLightbox();
        }
    });
}());
</script>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
