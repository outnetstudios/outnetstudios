<header id="appNavbar" class="sticky top-0 z-50 mx-auto mt-sm md:mt-lg w-full max-w-7xl flex justify-between items-center px-sm md:px-md py-sm bg-surface-container backdrop-blur-xl rounded-full border border-primary/10" style="box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 20px rgba(108,140,255,0.1);">
<div class="flex items-center gap-md">
<?php if (isset($navbarBackUrl) && $navbarBackUrl): ?>
<a href="<?= htmlspecialchars($navbarBackUrl, ENT_QUOTES, 'UTF-8') ?>" class="p-1.5 hover:bg-surface-variant/30 rounded-full text-on-surface-variant hover:text-primary transition-all" title="Volver"><span class="material-symbols-outlined">arrow_back</span></a>
<?php endif; ?>
<a href="index.php" class="font-display-lg-mobile text-display-lg-mobile font-extrabold tracking-tight no-underline hover:opacity-90 transition-opacity" style="background: linear-gradient(135deg, #6c8cff, #5ce1e6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Outnet Catalog</a>
</div>
<div class="flex items-center gap-sm">
<div class="hidden md:flex items-center gap-sm"><?= $navbarExtra ?? '' ?></div>
<div class="relative" id="userDropdown">
    <div onclick="toggleUserMenu()" class="flex items-center gap-1 cursor-pointer group">
        <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-primary/30 group-hover:border-primary transition-colors bg-surface-container-high flex items-center justify-center" style="box-shadow: 0 0 10px rgba(108,140,255,0.1);">
            <span class="material-symbols-outlined text-primary text-[20px]">person</span>
        </div>
        <span class="hidden md:block font-body-sm text-body-sm font-bold text-on-surface"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div id="userMenu" class="hidden absolute right-0 top-full mt-2 w-48 bg-surface-container backdrop-blur-xl rounded-2xl border border-primary/10 shadow-xl overflow-hidden" style="box-shadow: 0 8px 32px rgba(0,0,0,0.5);">
        <a href="collaborators.php" class="flex items-center gap-2 px-md py-sm hover:bg-surface-variant/30 transition-colors no-underline">
            <span class="material-symbols-outlined text-primary text-[18px]">group</span>
            <span class="font-body-sm text-body-sm text-on-surface">Colaboradores</span>
        </a>
        <hr class="border-outline-variant/20">
        <a href="../catalog_auth/logout.php" class="flex items-center gap-2 px-md py-sm hover:bg-error/10 transition-colors no-underline">
            <span class="material-symbols-outlined text-error/70 text-[18px]">logout</span>
            <span class="font-body-sm text-body-sm text-error/70">Cerrar sesión</span>
        </a>
    </div>
</div>
</div>
<script>
function toggleUserMenu() {
    var menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
    var dd = document.getElementById('userDropdown');
    if (dd && !dd.contains(e.target)) {
        document.getElementById('userMenu')?.classList.add('hidden');
    }
});
</script>
</header>