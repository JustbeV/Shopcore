import React, { PropsWithChildren, ReactNode } from 'react';

interface MerchantLayoutProps {
  storeId: string;
  header?: ReactNode;
}

export default function MerchantLayout({ children, header }: PropsWithChildren<MerchantLayoutProps>) {
  return (
    <div className="min-h-screen bg-gray-50">
      {header && (
        <header className="border-b bg-white py-4 shadow-sm">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">{header}</div>
        </header>
      )}
      <main className="mx-auto max-w-7xl p-6">{children}</main>
    </div>
  );
}