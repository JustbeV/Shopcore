import React from 'react';
import { usePage, Link } from '@inertiajs/react';
import SEOHead from '@/Components/Storefront/SEOHead';
import { SharedPageProps, Product } from '@/types';

interface PaginatedData<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface IndexProps extends SharedPageProps {
    products: PaginatedData<Product>;
}

export default function Index({ products }: IndexProps) {
    const { store } = usePage<SharedPageProps>().props;

    return (
        <>
            <SEOHead 
                title="All Products"
                description={`Browse all products at ${store.name}`}
            />

            <main className="min-h-screen bg-gray-50 p-6">
                <div className="max-w-7xl mx-auto py-6">
                    <h1 className="text-3xl font-bold tracking-tight text-gray-900 mb-6">
                        Catalog
                    </h1>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {products.data.map((product) => (
                            <Link 
                                key={product.id} 
                                href={`/products/${product.slug}`}
                                className="bg-white p-4 rounded-lg shadow-sm border hover:shadow-md transition"
                            >
                                <h3 className="font-semibold text-lg text-gray-900">{product.name}</h3>
                                <p className="text-gray-600 mt-1">{product.price_formatted}</p>
                            </Link>
                        ))}
                    </div>
                </div>
            </main>
        </>
    );
}