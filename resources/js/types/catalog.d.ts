export interface Category {
  id: string;
  name: string;
  slug: string;
}

export interface Product {
  id: string;
  title: string;
  slug: string;
  description: string | null;
  base_price_cents: number;
  currency: string;
  categories: Category[];
}