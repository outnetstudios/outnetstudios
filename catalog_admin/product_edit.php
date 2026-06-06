<?php
require_once __DIR__ . '/../includes/catalog_auth_helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';
require_once __DIR__ . '/../src/Repositories/ProductRepository.php';
require_once __DIR__ . '/../src/Repositories/CategoryRepository.php';

catalogRequireLogin();
$userName = htmlspecialchars(catalogGetUserName(), ENT_QUOTES, 'UTF-8');
$userId = (int)catalogGetUserId();
$id = (int)($_GET['id'] ?? 0);

$productRepo = new ProductRepository();
$product = $productRepo->findById($id);

if (!$product || (int)$product['user_id'] !== $userId) {
    header('Location: index.php');
    exit;
}

$catalogId = (int)$product['catalog_id'];
$categoryRepo = new CategoryRepository();
$categories = $categoryRepo->allByCatalog($catalogId);

$error = '';
$name = $product['name'];
$sku = $product['sku'] ?? '';
$description = $product['description'] ?? '';
$categoryId = (int)$product['category_id'];
$price = $product['price'];
$stock = $product['stock'];
$status = $product['status'];
$mainImage = $product['main_image'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $stock = $_POST['stock'] !== '' ? (int)$_POST['stock'] : null;
    $status = in_array($_POST['status'] ?? 'active', ['active','inactive']) ? $_POST['status'] : 'active';

    if ($name === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        $newImage = uploadImage($_FILES['main_image'] ?? []);
        if ($newImage) {
            deleteImage($product['main_image'] ?? null);
            $mainImage = $newImage;
        }
        $productRepo->update($id, [
            'category_id' => $categoryId,
            'name' => $name,
            'sku' => $sku,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'status' => $status,
            'main_image' => $mainImage,
        ]);
        header('Location: products.php?catalog_id=' . $catalogId);
        exit;
    }
}
?>
<?php $pageTitle = 'Editar producto'; require_once __DIR__ . '/../templates/partials/admin_head.php'; ?>
<?php $navbarBackUrl = 'products.php?catalog_id=' . $catalogId; require_once __DIR__ . '/../templates/partials/admin_navbar.php'; ?>
<main class="min-h-[calc(100vh-80px)]">
<section class="flex-1 p-margin-mobile md:p-margin-desktop overflow-y-auto custom-scrollbar">
<div class="max-w-5xl mx-auto">

<?php if ($error !== ''): ?>
<div class="glass-panel rounded-[24px] p-md mb-gutter border border-error/30">
<p class="text-error font-body-sm text-body-sm flex items-center gap-xs"><span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
</div>
<?php endif; ?>

<div class="glass-panel rounded-[24px] p-lg md:p-xl">
<form class="space-y-gutter" method="POST" enctype="multipart/form-data">
<div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
<div class="space-y-xs">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">Nombre del Producto *</label>
<input class="w-full h-14 bg-white/5 border border-outline-variant/30 rounded-[20px] px-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-1 focus:ring-primary focus:border-primary transition-all outline-none" type="text" id="name" name="name" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre del producto">
</div>
<div class="space-y-xs">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">SKU / Identificador</label>
<input class="w-full h-14 bg-white/5 border border-outline-variant/30 rounded-[20px] px-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-1 focus:ring-primary focus:border-primary transition-all outline-none" type="text" id="sku" name="sku" value="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>" placeholder="LM-PRO-001">
</div>
<div class="space-y-xs md:col-span-2">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">Imagen del producto</label>
<input class="w-full h-14 bg-white/5 border border-outline-variant/30 rounded-[20px] px-md text-on-surface file:bg-surface-variant/30 file:border-0 file:rounded-full file:px-sm file:py-1 file:text-label-caps file:text-primary file:cursor-pointer focus:ring-1 focus:ring-primary focus:border-primary transition-all outline-none" type="file" id="main_image" name="main_image" accept="image/jpeg,image/png,image/webp,image/gif">
<?php if ($mainImage): ?>
<div class="flex items-center gap-sm mt-xs">
<img src="<?= htmlspecialchars(imageUrl($mainImage), ENT_QUOTES, 'UTF-8') ?>" class="w-14 h-14 rounded-lg object-cover border border-outline-variant/30">
<span class="text-body-sm text-on-surface-variant/60">Imagen actual</span>
</div>
<?php endif; ?>
</div>
<div class="space-y-xs">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">Categoría</label>
<div class="relative">
<select class="w-full h-14 bg-surface-container-high border border-outline-variant/30 rounded-[20px] px-md text-on-surface appearance-none focus:ring-1 focus:ring-primary focus:border-primary transition-all outline-none" id="category_id" name="category_id">
<option value="0">Sin categoría</option>
<?php foreach ($categories as $cat): ?>
<option value="<?= $cat['id'] ?>" <?= (int)$cat['id'] === $categoryId ? 'selected' : '' ?>><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
<span class="material-symbols-outlined absolute right-md top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant">expand_more</span>
</div>
</div>
<div class="space-y-xs">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">Estado</label>
<div class="flex gap-xs bg-white/5 p-1 rounded-[20px] border border-outline-variant/30 h-14">
<button class="flex-1 rounded-[16px] <?= $status === 'active' ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-white/5' ?> font-label-caps text-label-caps" type="button" onclick="document.getElementById('status_active').checked=true; this.closest('.flex').querySelectorAll('button').forEach(b=>b.className='flex-1 rounded-[16px] text-on-surface-variant font-label-caps text-label-caps hover:bg-white/5'); this.className='flex-1 rounded-[16px] bg-secondary-container text-on-secondary-container font-label-caps text-label-caps'">Publicado</button>
<button class="flex-1 rounded-[16px] <?= $status === 'inactive' ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-white/5' ?> font-label-caps text-label-caps" type="button" onclick="document.getElementById('status_inactive').checked=true; this.closest('.flex').querySelectorAll('button').forEach(b=>b.className='flex-1 rounded-[16px] text-on-surface-variant font-label-caps text-label-caps hover:bg-white/5'); this.className='flex-1 rounded-[16px] bg-secondary-container text-on-secondary-container font-label-caps text-label-caps'">Borrador</button>
</div>
<input type="radio" id="status_active" name="status" value="active" class="hidden" <?= $status === 'active' ? 'checked' : '' ?>>
<input type="radio" id="status_inactive" name="status" value="inactive" class="hidden" <?= $status === 'inactive' ? 'checked' : '' ?>>
</div>
<div class="space-y-xs">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">Precio (USD)</label>
<div class="relative">
<span class="absolute left-md top-1/2 -translate-y-1/2 text-tertiary">$</span>
<input class="w-full h-14 bg-white/5 border border-outline-variant/30 rounded-[20px] pl-xl pr-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-1 focus:ring-primary focus:border-primary transition-all outline-none" type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" value="<?= $price !== null ? htmlspecialchars((string)$price, ENT_QUOTES, 'UTF-8') : '' ?>">
</div>
</div>
<div class="space-y-xs">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">Stock Disponible</label>
<input class="w-full h-14 bg-white/5 border border-outline-variant/30 rounded-[20px] px-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-1 focus:ring-primary focus:border-primary transition-all outline-none" type="number" id="stock" name="stock" min="0" placeholder="99" value="<?= $stock !== null ? htmlspecialchars((string)$stock, ENT_QUOTES, 'UTF-8') : '' ?>">
</div>
</div>
<div class="space-y-xs">
<label class="font-label-caps text-label-caps text-primary uppercase ml-xs">Descripción del Producto</label>
<textarea class="w-full bg-white/5 border border-outline-variant/30 rounded-[20px] p-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-1 focus:ring-primary focus:border-primary transition-all outline-none resize-none" id="description" name="description" rows="5" placeholder="Describe las especificaciones técnicas, materiales y beneficios del producto..."><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
</div>
<div class="pt-md border-t border-white/5 flex justify-between items-center">
<a href="products.php?catalog_id=<?= $catalogId ?>" class="px-lg py-sm rounded-full border border-outline-variant font-title-sm text-title-sm text-on-surface-variant hover:bg-surface-variant/50 hover:border-error/30 hover:text-error transition-all no-underline">Cancelar</a>
<button class="primary-gradient text-on-primary px-xl py-sm rounded-full font-title-sm text-title-sm btn-glow active:scale-95 transition-all shadow-lg" type="submit">Guardar cambios</button>
</div>
</form>
</div>
</div>
</section>
</main>
</body>
</html>
