import React from 'react';
import { usePage } from '@inertiajs/react';
import SEOHead from '@/Components/Storefront/SEOHead';
import { SharedPageProps, Product } from '@/types';

interface HomeProps extends SharedPageProps {
    featuredProducts: Product[];
}

export default function Home({ featuredProducts }: HomeProps) {
    const { store } = usePage<SharedPageProps>().props;

    return (
        <>
            <SEOHead 
                title="Welcome"
                description={`Browse products at ${store.name}`}
            />

            <main className="min-h-screen bg-gray-50 p-6">
                <header className="max-w-7xl mx-auto py-8">
                    <h1 className="text-4xl font-bold tracking-tight text-gray-900">
                        {store.name}
                    </h1>
                </header>

                <section className="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {featuredProducts?.map((product) => (
                        <div key={product.id} className="bg-white p-4 rounded-lg shadow-sm border">
                            <h3 className="font-semibold text-lg">{product.name}</h3>
                            <p className="text-gray-600 mt-1">{product.price_formatted}</p>
                        </div>
                    ))}
                </section>
            </main>
        </>
    );
}