<?php
// Widget: auth form
// Variables esperadas:
// - $authPanelClass: clase del panel contenedor (default: 'login-panel')
// - $authTitle: título principal
// - $authSubtitle: texto descriptivo opcional
// - $authErrorMessage: mensaje de error opcional
// - $authSuccessMessage: mensaje de éxito opcional
// - $authFormAction: URL de envío del formulario
// - $authFormMethod: método HTTP del formulario (default: 'post')
// - $authFormId: id opcional del formulario
// - $authButtonText: texto del botón principal
// - $authFields: arreglo de campos de formulario
// - $authLinks: arreglo de enlaces adicionales al pie

$authPanelClass = $authPanelClass ?? 'login-panel';
$authTitle = $authTitle ?? '';
$authSubtitle = $authSubtitle ?? '';
$authErrorMessage = $authErrorMessage ?? '';
$authSuccessMessage = $authSuccessMessage ?? '';
$authFormAction = $authFormAction ?? '';
$authFormMethod = $authFormMethod ?? 'post';
$authFormId = $authFormId ?? '';
$authButtonText = $authButtonText ?? 'Enviar';
$authFields = $authFields ?? [];
$authLinks = $authLinks ?? [];
$authShowForm = $authShowForm ?? true;
$authFooterHtml = $authFooterHtml ?? '';
?>
<div class="auth-panel <?= htmlspecialchars($authPanelClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($authTitle !== ''): ?>
        <h2><?= htmlspecialchars($authTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <?php endif; ?>

    <?php if ($authSubtitle !== ''): ?>
        <p><?= htmlspecialchars($authSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($authErrorMessage !== ''): ?>
        <div class="error-message"><?= htmlspecialchars($authErrorMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($authSuccessMessage !== ''): ?>
        <div class="message success"><?= htmlspecialchars($authSuccessMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($authShowForm && !empty($authFields)): ?>
        <form class="auth-form"<?php if ($authFormId !== ''): ?> id="<?= htmlspecialchars($authFormId, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?> method="<?= htmlspecialchars($authFormMethod, ENT_QUOTES, 'UTF-8') ?>" action="<?= htmlspecialchars($authFormAction, ENT_QUOTES, 'UTF-8') ?>">
            <?php foreach ($authFields as $field): ?>
            <?php
                $type = $field['type'] ?? 'text';
                $fieldId = $field['id'] ?? $field['name'] ?? '';
                $fieldName = $field['name'] ?? $fieldId;
                $fieldLabel = $field['label'] ?? '';
                $fieldPlaceholder = $field['placeholder'] ?? '';
                $fieldValue = $field['value'] ?? '';
                $fieldRequired = !empty($field['required']);
                $fieldAttrs = $field['attributes'] ?? '';
                $fieldClass = $field['class'] ?? '';
            ?>

            <?php if ($type === 'hidden'): ?>
                <input type="hidden" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>" <?= $fieldAttrs ?> />
            <?php else: ?>
                <div class="form-group">
                    <?php if ($fieldLabel !== ''): ?>
                        <label for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php endif; ?>
                    <?php if ($type === 'textarea'): ?>
                        <textarea name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($fieldPlaceholder, ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($fieldClass, ENT_QUOTES, 'UTF-8') ?>" <?= $fieldRequired ? 'required' : '' ?> <?= $fieldAttrs ?>><?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php else: ?>
                        <input type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($fieldPlaceholder, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($fieldClass, ENT_QUOTES, 'UTF-8') ?>" <?= $fieldRequired ? 'required' : '' ?> <?= $fieldAttrs ?> />
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <button class="button-primary" type="submit"><?= htmlspecialchars($authButtonText, ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    <?php endif; ?>

    <?= $authFooterHtml ?>

    <?php if (!empty($authLinks)): ?>
        <div class="link" style="text-align:center; margin-top:1rem;">
            <?php foreach ($authLinks as $index => $link): ?>
                <a class="auth-link" href="<?= htmlspecialchars($link['href'] ?? '#', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($link['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
                <?php if ($index < count($authLinks) - 1): ?>
                    &nbsp;|&nbsp;
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
