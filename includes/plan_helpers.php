<?php

function normalizePlanInteresValue(string $value): string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }

    $normalized = function_exists('mb_strtolower') ? mb_strtolower($trimmed, 'UTF-8') : strtolower($trimmed);

    $noAccents = $normalized;
    if (function_exists('iconv')) {
        $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $noAccents);
        if ($tmp !== false) {
            $noAccents = $tmp;
        }
    }

    $key = preg_replace('/[^a-z0-9\s]/i', '', $noAccents);
    $key = preg_replace('/\s+/', ' ', $key);
    $key = trim($key);

    $map = [
        'basic' => 'basic',
        'basico' => 'basic',
        'plan basico' => 'basic',
        'plan basico ' => 'basic',
        'professional' => 'professional',
        'profesional' => 'professional',
        'plan profesional' => 'professional',
        'premium' => 'premium',
        'plan premium' => 'premium',
        'aplicaciones' => 'aplicaciones',
        'sitio web' => 'sitio-web',
        'sitio-web' => 'sitio-web',
        'tienda online' => 'tienda-prestashop',
        'tienda-online' => 'tienda-prestashop',
        'tienda prestashop' => 'tienda-prestashop',
        'tienda-prestashop' => 'tienda-prestashop',
        'tienda woocommerce' => 'tienda-woocommerce',
        'tienda-woocommerce' => 'tienda-woocommerce',
        'otro proyecto' => 'otro-proyecto',
        'otro-proyecto' => 'otro-proyecto',
    ];

    if (isset($map[$key])) {
        return $map[$key];
    }

    if (isset($map[$normalized])) {
        return $map[$normalized];
    }

    return $trimmed;
}

function formatPlanInteresLabel(string $value): string
{
    $normalized = normalizePlanInteresValue($value);
    $map = [
        'basic' => 'Plan Básico',
        'professional' => 'Plan Profesional',
        'premium' => 'Plan Premium',
        'aplicaciones' => 'Aplicación para Android o Windows',
        'sitio-web' => 'Sitio web',
        'tienda-prestashop' => 'Tienda online con PrestaShop',
        'tienda-woocommerce' => 'Tienda online con WooCommerce',
        'otro-proyecto' => 'Otro proyecto digital',
    ];

    return $map[$normalized] ?? $normalized;
}
