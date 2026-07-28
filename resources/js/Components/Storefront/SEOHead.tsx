import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { SharedPageProps } from '@/types';

interface SEOHeadProps {
    title?: string;
    description?: string;
    image?: string | null;
    canonicalUrl?: string;
    type?: 'website' | 'article' | 'product';
    schema?: Record<string, unknown> | null;
}

export default function SEOHead({ 
    title, 
    description, 
    image, 
    canonicalUrl, 
    type = 'website',
    schema = null 
}: SEOHeadProps) {
    const { store } = usePage<SharedPageProps>().props;

    const pageTitle = title ? `${title} | ${store?.name}` : store?.name;
    const metaDescription = description || `${store?.name} official storefront.`;
    const ogImage = image || store?.logo_url;

    return (
        <Head>
            <title>{pageTitle}</title>
            <meta name="description" content={metaDescription} />

            {/* OpenGraph / Facebook */}
            <meta property="og:type" content={type} />
            <meta property="og:title" content={pageTitle} />
            <meta property="og:description" content={metaDescription} />
            {ogImage && <meta property="og:image" content={ogImage} />}
            {canonicalUrl && <meta property="og:url" content={canonicalUrl} />}

            {/* Twitter */}
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content={pageTitle} />
            <meta name="twitter:description" content={metaDescription} />
            {ogImage && <meta name="twitter:image" content={ogImage} />}

            {/* Canonical Link */}
            {canonicalUrl && <link rel="canonical" href={canonicalUrl} />}

            {/* Structured Data (JSON-LD) */}
            {schema && (
                <script type="application/ld+json">
                    {JSON.stringify(schema)}
                </script>
            )}
        </Head>
    );
}