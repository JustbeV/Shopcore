import React from 'react';
import { usePage } from '@inertiajs/react';
import SEOHead from '@/Components/Storefront/SEOHead';
import { SharedPageProps } from '@/types';

interface ProductDetail {
    id: number;
    name: string;
    slug: string;
    description: string;
    price_formatted: string;
    images?: Array<{ url: string; alt_text?: string }>;
}

interface ShowProps extends SharedPageProps {
    product: ProductDetail;
}

export default function Show({ product }: ShowProps) {
    const { store } = usePage<SharedPageProps>().props;

    const schema = {
        '@context': 'https://schema.org/',
        '@type': 'Product',
        'name': product.name,
        'description': product.description,
        'offers': {
            '@type': 'Offer',
            'priceCurrency': store.currency,
            'availability': 'https://schema.org/InStock',
        }
    };

    return (
        <>
            <SEOHead 
                title={product.name}
                description={product.description}
                type="product"
                schema={schema}
            />

            <main className="min-h-screen bg-gray-50 p-6">
                <div className="max-w-5xl mx-auto bg-white rounded-xl border p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div className="bg-gray-100 rounded-lg aspect-square flex items-center justify-center text-gray-400">
                        {product.images?.[0] ? (
                            <img src={product.images[0].url} alt={product.images[0].alt_text || product.name} className="w-full h-full object-cover rounded-lg" />
                        ) : (
                            <span>No Image Available</span>
                        )}
                    </div>

                    <div className="flex flex-col justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{product.name}</h1>
                            <p className="text-2xl font-semibold text-indigo-600 mt-2">{product.price_formatted}</p>
                            <p className="text-gray-600 mt-4 leading-relaxed">{product.description}</p>
                        </div>

                        <button 
                            disabled 
                            className="w-full py-3 bg-gray-300 text-gray-600 rounded-lg font-medium cursor-not-allowed"
                        >
                            Add to Cart (Phase 5)
                        </button>
                    </div>
                </div>
            </main>
        </>
    );
}