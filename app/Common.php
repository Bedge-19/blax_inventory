<?php

if (!function_exists('cloudinary_transform_url')) {
    /**
     * Transform a Cloudinary image URL to a named preset (card, thumbnail, avatar, detail, logo, banner).
     * Automatically applies optimal format (WebP/AVIF) and quality compression.
     */
    function cloudinary_transform_url(?string $url, string $variant = 'card'): string
    {
        return \App\Libraries\CloudinaryService::transformUrl($url, $variant);
    }
}

if (!function_exists('product_image_url')) {
    /**
     * Resolve a product image to a renderable URL with Cloudinary optimization.
     * Default variant is 'card' (480x480 max with f_auto,q_auto).
     * Pass 'thumbnail' for 160x160 or 'detail' for 960x960.
     */
    function product_image_url(?string $url, string $variant = 'card', string $fallback = 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80'): string
    {
        if (str_starts_with($variant, 'http://') || str_starts_with($variant, 'https://')) {
            $fallback = $variant;
            $variant  = 'card';
        }

        if ($url === null || trim($url) === '') {
            return $fallback;
        }

        $url = trim($url);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return cloudinary_transform_url($url, $variant);
        }

        if (defined('FCPATH')) {
            $localDiskPath = FCPATH . ltrim($url, '/\\');
            if (!is_file($localDiskPath)) {
                return $fallback;
            }
        }

        return base_url($url);
    }
}

if (!function_exists('cms_image_url')) {
    function cms_image_url(?string $url, string $variant = 'banner', string $fallback = 'https://images.unsplash.com/photo-1556742049-0a67daf64f42?auto=format&fit=crop&w=1440&q=80'): string
    {
        if (str_starts_with($variant, 'http://') || str_starts_with($variant, 'https://')) {
            $fallback = $variant;
            $variant  = 'banner';
        }

        $url = trim((string) $url);
        if ($url === '') {
            return $fallback;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return cloudinary_transform_url($url, $variant);
        }

        if (defined('FCPATH')) {
            $localDiskPath = FCPATH . ltrim($url, '/\\');
            if (!is_file($localDiskPath)) {
                return $fallback;
            }
        }

        return base_url($url);
    }
}

if (!function_exists('logo_url')) {
    function logo_url(?string $url, string $variant = 'logo'): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }

        $url = trim($url);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return cloudinary_transform_url($url, $variant);
        }

        if (defined('FCPATH')) {
            $localDiskPath = FCPATH . ltrim($url, '/\\');
            if (!is_file($localDiskPath)) {
                return '';
            }
        }

        return base_url($url);
    }
}

if (!function_exists('profile_image_url')) {
    function profile_image_url(?string $url, string $variant = 'avatar'): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }

        $url = trim($url);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return cloudinary_transform_url($url, $variant);
        }

        if (defined('FCPATH')) {
            $localDiskPath = FCPATH . ltrim($url, '/\\');
            if (!is_file($localDiskPath)) {
                return '';
            }
        }

        return base_url($url);
    }
}

if (!function_exists('humanize_status')) {
    function humanize_status(?string $status): string
    {
        if ($status === null || trim($status) === '') {
            return 'Pending';
        }
        $normalized = str_replace(['_', '-'], ' ', strtolower(trim($status)));
        return ucwords($normalized);
    }
}
