<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $sessionId   = (int) ($session['id'] ?? 0);
    $doctorName  = (string) ($doctor['name'] ?? 'Doctor');
    $planDate    = (string) ($session['plan_date'] ?? date('Y-m-d'));
    $presentationSlides = (array) ($presentationSlides ?? []);
    $totalSlides = count($presentationSlides); // only product slides
?>

<!-- Full-screen presentation overlay — covers sidebar/topbar completely -->
<div id="presentationMode"
     class="fixed inset-0 bg-black z-[9999] flex flex-col"
     style="top:0;left:0;right:0;bottom:0;">
    
    <!-- Close / End Call button (Always visible) -->
    <div class="absolute top-8 right-8 z-[10000]">
        <button type="button"
                onclick="confirmEndCall()"
                class="bg-zinc-800 hover:bg-red-700 text-white px-6 py-2 rounded-full font-bold transition border-0 cursor-pointer flex items-center gap-2">
            <i class="fa-solid fa-times"></i> Close
        </button>
    </div>
    
    <!-- ── Slides ─────────────────────────────────── -->
    <?php foreach ($presentationSlides as $idx => $slide): ?>
    <div class="slide-item <?= $idx === 0 ? '' : 'hidden' ?> absolute inset-0 w-full h-full bg-zinc-950 flex flex-col justify-center p-0 m-0 text-white overflow-hidden border-0">
        <?php if (! empty($slide['file_path'])): ?>
            <?php if (($slide['type'] ?? '') === 'image'): ?>
                <img src="/<?= esc($slide['file_path']) ?>" 
                     alt="<?= esc($slide['product_name'] . ' - ' . $slide['slide_title']) ?>"
                     class="w-full h-full object-contain"
                     style="width: 100vw; height: 100vh; display: block; margin: 0 auto; background-color: #000;">
            <?php else: ?>
                <iframe src="/<?= esc($slide['file_path']) ?>#toolbar=0&navpanes=0&scrollbar=0&view=Fit"
                        title="<?= esc($slide['product_name'] . ' - ' . $slide['slide_title']) ?>"
                        class="w-full h-full border-0 m-0 p-0 block"
                        style="width: 100vw; height: 100vh; border: none; margin: 0; padding: 0; display: block;"></iframe>
            <?php endif; ?>
        <?php else: ?>
            <div class="w-full h-full flex flex-col items-center justify-center text-zinc-500 bg-zinc-950 p-0 m-0 border-0">
                <i class="fas fa-file-medical text-6xl mb-3 text-teal-700"></i>
                <span class="text-lg font-bold">No Slide or PDF available for <?= esc($slide['product_name']) ?></span>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>



    <!-- ── Left nav chevron ─── -->
    <button type="button"
            id="btn-prev"
            onclick="changeSlide(-1)"
            class="absolute left-6 top-1/2 -translate-y-1/2 bg-zinc-900/70 hover:bg-zinc-800 border border-zinc-700 text-white w-16 h-16 rounded-full z-10 flex items-center justify-center cursor-pointer border-0 opacity-40"
            style="display:flex;">
        <i class="fa-solid fa-chevron-left text-2xl"></i>
    </button>

    <!-- ── Right nav chevron ─── -->
    <button type="button"
            id="btn-next"
            onclick="changeSlide(1)"
            class="absolute right-6 top-1/2 -translate-y-1/2 bg-zinc-900/70 hover:bg-zinc-800 border border-zinc-700 text-white w-16 h-16 rounded-full z-10 flex items-center justify-center cursor-pointer border-0"
            style="display:flex;">
        <i class="fa-solid fa-chevron-right text-2xl"></i>
    </button>

    <!-- ── Dots indicator ─── -->
    <div id="slideDotsContainer"
         class="absolute bottom-6 left-0 right-0 flex justify-center gap-3 z-10">
    </div>
</div>

<!-- End-call hidden form (posted on close) -->
<form id="end-call-form"
      method="post"
      action="/phadmin/e-detailing/end-call/<?= $sessionId ?>"
      class="hidden m-0 p-0 border-0 bg-transparent">
    <input type="hidden" name="feedback"       id="ec-feedback"  value="">
    <input type="hidden" name="next_objective" value="">
    <input type="hidden" name="joint_call_with" value="">
</form>

<script>
(function () {
    const TOTAL  = <?= (int) $totalSlides ?>;
    const SESSION_ID = <?= $sessionId ?>;
    let active   = 0;

    const slides  = () => document.querySelectorAll('.slide-item');
    const prevBtn = document.getElementById('btn-prev');
    const nextBtn = document.getElementById('btn-next');
    const dots    = document.getElementById('slideDotsContainer');

    function render() {
        slides().forEach((s, i) => {
            s.classList.toggle('hidden', i !== active);
        });

        /* prev button */
        if (prevBtn) {
            prevBtn.style.opacity = active > 0 ? '1' : '0.2';
            prevBtn.style.pointerEvents = active > 0 ? 'auto' : 'none';
        }

        /* next button — disable on last slide */
        if (nextBtn) {
            const onLastSlide = active === TOTAL - 1;
            nextBtn.style.opacity = onLastSlide ? '0.2' : '1';
            nextBtn.style.pointerEvents = onLastSlide ? 'none' : 'auto';
        }

        /* dots */
        if (dots) {
            dots.innerHTML = Array.from({ length: TOTAL }).map((_, i) =>
                '<span class="rounded-full transition-all duration-300 ' +
                (i === active
                    ? 'bg-teal-400 w-4 h-2 inline-block'
                    : 'bg-zinc-700 w-2 h-2 inline-block') +
                '"></span>'
            ).join('');
        }
    }

    window.changeSlide = function (dir) {
        const next = active + dir;
        if (next < 0 || next >= TOTAL) return;
        active = next;
        render();
    };

    window.confirmEndCall = function () {
        if (!confirm('End this detailing session and save the activity?')) return;
        document.getElementById('ec-feedback').value = 'Session completed via presentation.';
        document.getElementById('end-call-form').submit();
    };

    /* keyboard navigation */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') changeSlide(1);
        if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   changeSlide(-1);
    });

    /* touch swipe */
    let touchStartX = 0;
    const el = document.getElementById('presentationMode');
    if (el) {
        el.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        el.addEventListener('touchend', function (e) {
            const delta = touchStartX - e.changedTouches[0].screenX;
            if (delta > 60)  changeSlide(1);
            if (delta < -60) changeSlide(-1);
        }, { passive: true });
    }

    // Auto request full screen on first click/keypress
    const requestFS = () => {
        const docEl = document.documentElement;
        if (docEl.requestFullscreen) {
            docEl.requestFullscreen().catch(() => {});
        } else if (docEl.webkitRequestFullscreen) {
            docEl.webkitRequestFullscreen();
        } else if (docEl.msRequestFullscreen) {
            docEl.msRequestFullscreen();
        }
        document.removeEventListener('click', requestFS);
        document.removeEventListener('keydown', requestFS);
    };
    document.addEventListener('click', requestFS);
    document.addEventListener('keydown', requestFS);

    render();
})();
</script>

<!-- Hide the main layout's sidebar/topbar behind the overlay — nothing else needed -->
<?= $this->endSection() ?>
