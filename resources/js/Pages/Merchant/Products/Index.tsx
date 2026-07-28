import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import type { Category, Product } from '@/types/catalog';

interface Props {
  storeId: string;
  categories: Category[];
  product?: Product; // present when editing, absent when creating
}

export default function ProductForm({ storeId, categories, product }: Props) {
  const isEditing = product !== undefined;

  // 1. Destructure `transform` from useForm
  const { data, setData, post, put, transform, processing, errors } = useForm({
    title: product?.title ?? '',
    slug: product?.slug ?? '',
    description: product?.description ?? '',
    base_price_cents: product ? product.base_price_cents / 100 : 0,
    currency: product?.currency ?? 'USD',
    category_ids: product?.categories?.map((c) => c.id) ?? ([] as string[]),
  });

  // 2. Register the transformation (converts price to cents before sending)
  transform((formData) => ({
    ...formData,
    base_price_cents: Math.round(Number(formData.base_price_cents) * 100),
  }));

  function submit(e: FormEvent) {
    e.preventDefault();

    // 3. Call put/post with ONLY the URL (Inertia handles the payload automatically)
    if (isEditing) {
      put(`/api/v1/tenant/stores/${storeId}/products/${product.id}`);
    } else {
      post(`/api/v1/tenant/stores/${storeId}/products`);
    }
  }

  function toggleCategory(categoryId: string) {
    setData(
      'category_ids',
      data.category_ids.includes(categoryId)
        ? data.category_ids.filter((id) => id !== categoryId)
        : [...data.category_ids, categoryId],
    );
  }

  return (
    <MerchantLayout
      storeId={storeId}
      header={
        <h1 className="text-lg font-semibold text-gray-900">
          {isEditing ? `Edit ${product.title}` : 'New product'}
        </h1>
      }
    >
      <Head title={isEditing ? 'Edit product' : 'New product'} />

      <form onSubmit={submit} className="max-w-2xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        <div>
          <Label htmlFor="title">Title</Label>
          <Input
            id="title"
            value={data.title}
            onChange={(e) => setData('title', e.target.value)}
            aria-invalid={!!errors.title}
          />
          {errors.title && <p className="mt-1 text-xs text-red-600">{errors.title}</p>}
        </div>

        <div>
          <Label htmlFor="slug">Slug</Label>
          <Input
            id="slug"
            value={data.slug}
            onChange={(e) => setData('slug', e.target.value)}
            aria-invalid={!!errors.slug}
          />
          {errors.slug && <p className="mt-1 text-xs text-red-600">{errors.slug}</p>}
        </div>

        <div>
          <Label htmlFor="description">Description</Label>
          <Textarea
            id="description"
            value={data.description ?? ''}
            onChange={(e) => setData('description', e.target.value)}
          />
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <Label htmlFor="price">Price</Label>
            <Input
              id="price"
              type="number"
              step="0.01"
              min="0"
              value={data.base_price_cents}
              onChange={(e) => setData('base_price_cents', Number(e.target.value))}
              aria-invalid={!!errors.base_price_cents}
            />
            {errors.base_price_cents && <p className="mt-1 text-xs text-red-600">{errors.base_price_cents}</p>}
          </div>
          <div>
            <Label htmlFor="currency">Currency</Label>
            <Input
              id="currency"
              maxLength={3}
              value={data.currency}
              onChange={(e) => setData('currency', e.target.value.toUpperCase())}
            />
          </div>
        </div>

        {categories.length > 0 && (
          <div>
            <Label>Categories</Label>
            <div className="flex flex-wrap gap-2">
              {categories.map((category) => (
                <button
                  type="button"
                  key={category.id}
                  onClick={() => toggleCategory(category.id)}
                  className={
                    'rounded-full border px-3 py-1 text-xs ' +
                    (data.category_ids.includes(category.id)
                      ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                      : 'border-gray-300 text-gray-600')
                  }
                >
                  {category.name}
                </button>
              ))}
            </div>
          </div>
        )}

        {!isEditing && (
          <p className="text-xs text-gray-500">
            New products start as drafts. Add at least one variant after creating it, then publish from the product
            page.
          </p>
        )}

        <div className="flex justify-end gap-2 pt-2">
          <Button type="submit" disabled={processing}>
            {isEditing ? 'Save changes' : 'Create product'}
          </Button>
        </div>
      </form>
    </MerchantLayout>
  );
}