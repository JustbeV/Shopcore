export interface StoreBranding {
    primary_color: string;
    accent_color: string;
    font_family: string;
}

export interface Store {
    id: number | string;
    name: string;
    slug: string;
    logo_url: string | null;
    favicon_url: string | null;
    currency: string;
    branding: StoreBranding;
}

export interface ProductImage {
    id: number;
    url: string;
    alt_text?: string;
}

export interface Product {
    id: number;
    name: string;
    slug: string;
    description?: string;
    price_formatted: string;
    is_published?: boolean;
    is_featured?: boolean;
    images?: ProductImage[];
}

export interface SharedPageProps {
    store: Store;
    flash: {
        message?: string | null;
    };
    [key: string]: unknown;
}