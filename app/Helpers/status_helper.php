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

if (!function_exists('cms_image_url')) {
    /**
     * Resolve a CMS banner or content image to a renderable URL.
     * Handles null, empty string, local uploads ("uploads/..."), external URLs,
     * and applies the provided fallback if empty.
     */
    function cms_image_url(?string $url, string $fallback = 'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80'): string
    {
        $url = trim((string) $url);
        if ($url === '') {
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

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return strpos($url, 'uploads/') === 0 ? base_url($url) : base_url($url);
    }
}

if (!function_exists('profile_image_url')) {
    /**
     * Resolve a user or customer profile image to a renderable URL.
     * Supports both Cloudinary URLs (http/https) and legacy local upload paths.
     */
    function profile_image_url(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return base_url($url);
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
            // pending / amber
            'pending'          => ['bg-amber-100 text-amber-800 border border-amber-300', 'dot:amber-600'],
            'new'              => ['bg-amber-100 text-amber-800 border border-amber-300', 'dot:amber-600'],
            'inactive'         => ['bg-surface-variant text-on-surface-variant border border-outline-variant/40', 'dot:on-surface-variant'],
            // processing / blue
            'processing'       => ['bg-blue-100 text-blue-800 border border-blue-300', 'dot:blue-600'],
            'transfer_pending' => ['bg-purple-100 text-purple-800 border border-purple-300', 'dot:purple-600'],
            'in_production'    => ['bg-blue-100 text-blue-800 border border-blue-300', 'dot:blue-600'],
            'under_review'     => ['bg-blue-100 text-blue-800 border border-blue-300', 'dot:blue-600'],
            // transit / ready
            'shipped'          => ['bg-indigo-100 text-indigo-800 border border-indigo-300', 'dot:indigo-600'],
            'in_transit'       => ['bg-indigo-100 text-indigo-800 border border-indigo-300', 'dot:indigo-600'],
            'ready_for_pickup' => ['bg-teal-100 text-teal-800 border border-teal-300', 'dot:teal-600'],
            'ready_for_delivery' => ['bg-teal-100 text-teal-800 border border-teal-300', 'dot:teal-600'],
            'flagged'          => ['bg-orange-100 text-orange-800 border border-orange-300', 'dot:orange-600'],
            // positive / completed / green
            'active'           => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'dot:emerald-600'],
            'completed'        => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'dot:emerald-600'],
            'delivered'        => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'dot:emerald-600'],
            'returned'         => ['bg-purple-100 text-purple-800 border border-purple-300', 'dot:purple-600'],
            'success'          => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'dot:emerald-600'],
            'resolved'         => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'dot:emerald-600'],
            'verified'         => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'dot:emerald-600'],
            'in_stock'         => ['bg-emerald-100 text-emerald-800 border border-emerald-300', 'dot:emerald-600'],
            'low_stock'        => ['bg-amber-100 text-amber-800 border border-amber-300', 'dot:amber-600'],
            'out_of_stock'     => ['bg-rose-100 text-rose-800 border border-rose-300', 'dot:rose-600'],
            // negative / cancelled / red
            'cancelled'        => ['bg-rose-100 text-rose-800 border border-rose-300', 'dot:rose-600'],
            'failed'           => ['bg-rose-100 text-rose-800 border border-rose-300', 'dot:rose-600'],
            'suspended'        => ['bg-rose-100 text-rose-800 border border-rose-300', 'dot:rose-600'],
            'rejected'         => ['bg-rose-100 text-rose-800 border border-rose-300', 'dot:rose-600'],
            'refunded'         => ['bg-rose-100 text-rose-800 border border-rose-300', 'dot:rose-600'],
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
