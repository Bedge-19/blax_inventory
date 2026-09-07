<?php

if (!function_exists('product_image_url')) {
    /**
     * Resolve a product image to a renderable URL.
     * Locally-uploaded images (starting with "uploads/") are prefixed with base_url();
     * external URLs (http/https) are returned unchanged;
     * null/empty returns the default Unsplash placeholder.
     */
    function product_image_url(?string $url, string $fallback = 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80'): string
    {
        if ($url === null || trim($url) === '') {
            return $fallback;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return base_url($url);
    }
}

if (!function_exists('logo_url')) {
    /**
     * Resolve a shop logo to a renderable URL. Locally-uploaded logos are
     * stored as relative "uploads/..." paths and must be prefixed with the
     * base URL; external URLs are returned unchanged.
     */
    function logo_url(?string $url): string
    {
        if ($url === null || $url === '') {
            return '';
        }

        return strpos($url, 'uploads/') === 0 ? base_url($url) : $url;
    }
}

if (!function_exists('humanize_status')) {
    function humanize_status(?string $status): string
    {
        return ucwords(str_replace(['_', '-'], ' ', (string) $status));
    }
}

if (!function_exists('status_badge')) {
    /**
     * Render a status pill consistent with the Stitch prototypes.
     */
    function status_badge(?string $status, string $extra = ''): string
    {
        $status = strtolower((string) $status);
        $map = [
            // neutral
            'pending'          => ['bg-surface-variant text-on-surface-variant', 'dot:on-surface-variant'],
            'new'              => ['bg-primary-container/10 text-primary', 'dot:primary'],
            'inactive'         => ['bg-surface-variant text-on-surface-variant', 'dot:on-surface-variant'],
            'processing'       => ['bg-primary/10 text-primary', 'dot:primary'],
            'processing'       => ['bg-amber-100 text-amber-700', 'dot:amber-600'],
            // blue / in progress
            'shipped'          => ['bg-tertiary-container/10 text-tertiary-container', 'dot:tertiary-container'],
            'in_transit'       => ['bg-primary/10 text-primary', 'dot:primary'],
            'under_review'     => ['bg-tertiary-container/10 text-tertiary-container', 'dot:tertiary-container'],
            'in_production'    => ['bg-tertiary-container/10 text-tertiary-container', 'dot:tertiary-container'],
            'ready_for_pickup' => ['bg-secondary-container/40 text-secondary', 'dot:secondary'],
            'ready_for_delivery' => ['bg-outline/10 text-outline', 'dot:outline'],
            'flagged'          => ['bg-error-container text-on-error-container', 'dot:error'],
            // positive
            'active'           => ['bg-green-100 text-green-700', 'dot:green-600'],
            'completed'        => ['bg-[#dcfce7] text-[#166534]', 'dot:green-600'],
            'delivered'        => ['bg-emerald-100 text-emerald-800', 'dot:emerald-600'],
            'returned'         => ['bg-purple-100 text-purple-800', 'dot:purple-600'],
            'success'          => ['bg-emerald-100 text-emerald-800', 'dot:emerald-600'],
            'resolved'         => ['bg-[#dcfce7] text-[#166534]', 'dot:green-600'],
            'verified'         => ['bg-[#dcfce7] text-[#166534]', 'dot:green-600'],
            'in_stock'         => ['bg-green-100 text-green-700', 'dot:green-600'],
            'low_stock'        => ['bg-amber-100 text-amber-700', 'dot:amber-600'],
            'out_of_stock'     => ['bg-red-100 text-red-700', 'dot:red-600'],
            // negative
            'cancelled'        => ['bg-error/10 text-error', 'dot:error'],
            'failed'           => ['bg-error/10 text-error', 'dot:error'],
            'suspended'        => ['bg-error/10 text-error', 'dot:error'],
            'rejected'         => ['bg-error/10 text-error', 'dot:error'],
            'refunded'         => ['bg-error/10 text-error', 'dot:error'],
        ];

        if (isset($map[$status])) {
            [$classes, $dot] = $map[$status];
        } else {
            [$classes, $dot] = ['bg-surface-variant text-on-surface-variant', 'dot:on-surface-variant'];
        }

        $dotColor = str_replace('dot:', '', $dot);
        $label = humanize_status($status);

        return '<span class="inline-flex items-center gap-1.5 px-sm py-xs rounded-full text-[11px] font-semibold uppercase tracking-wide ' . $classes . ' ' . $extra . '"><span class="w-1.5 h-1.5 rounded-full bg-' . $dotColor . '"></span>' . esc($label) . '</span>';
    }
}
