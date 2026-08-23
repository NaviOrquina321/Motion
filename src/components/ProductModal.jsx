import React, { useState } from 'react';
import { X, Star, Plus } from 'lucide-react';

const PRODUCTS = [
  {
    id: 'wilson-pro-staff-97',
    name: 'Wilson Pro Staff 97',
    category: 'Rackets',
    price: 299,
    rating: 4.9,
    image: '/assets/wilson-pro-staff.jpg',
    description: 'Precision control and legendary feel for experienced players.'
  },
  {
    id: 'babolat-pure-drive',
    name: 'Babolat Pure Drive 2024',
    category: 'Rackets',
    price: 269,
    rating: 4.8,
    image: '/assets/wilson-pro-staff.jpg',
    description: 'Unmatched power and versatility on every shot.'
  },
  {
    id: 'head-speed-pro',
    name: 'Head Speed Pro Legend',
    category: 'Rackets',
    price: 279,
    rating: 4.9,
    image: '/assets/wilson-pro-staff.jpg',
    description: 'Built for fast-swinging tournament competitors.'
  },
  {
    id: 'nike-court-vapor-pro',
    name: 'NikeCourt Air Zoom Vapor',
    category: 'Shoes',
    price: 180,
    rating: 4.7,
    image: '/assets/wilson-pro-staff.jpg',
    description: 'Lightweight speed and responsive cushioning on hard courts.'
  },
  {
    id: 'pro-performance-bag',
    name: 'Tour 12-Pack Racket Bag',
    category: 'Accessories',
    price: 140,
    rating: 4.9,
    image: '/assets/wilson-pro-staff.jpg',
    description: 'Thermoguard protection with generous storage capacity.'
  },
  {
    id: 'dry-fit-court-polo',
    name: 'Pro Tournament Polo',
    category: 'Apparel',
    price: 75,
    rating: 4.6,
    image: '/assets/wilson-pro-staff.jpg',
    description: 'Breathable moisture-wicking fabric for maximum comfort.'
  }
];

export default function ProductModal({ isOpen, onClose, onAddToCart }) {
  const [selectedCategory, setSelectedCategory] = useState('All');

  if (!isOpen) return null;

  const categories = ['All', 'Rackets', 'Shoes', 'Apparel', 'Accessories'];

  const filteredProducts = selectedCategory === 'All'
    ? PRODUCTS
    : PRODUCTS.filter(p => p.category === selectedCategory);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"
        onClick={onClose}
      />

      {/* Modal Card */}
      <div className="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden z-10 my-8">

        {/* Header */}
        <div className="px-6 py-5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
          <div>
            <h2 className="text-xl font-bold">Tennis Equipment Collection</h2>
            <p className="text-xs text-slate-400 mt-0.5">Explore premium gear for tournament & casual play</p>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-full transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Category Filters */}
        <div className="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center gap-2 overflow-x-auto">
          {categories.map(cat => (
            <button
              key={cat}
              onClick={() => setSelectedCategory(cat)}
              className={`px-4 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer whitespace-nowrap ${
                selectedCategory === cat
                  ? 'bg-[#6e831c] text-white shadow-sm'
                  : 'bg-white text-slate-600 hover:bg-slate-200 border border-slate-200'
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {/* Product Grid */}
        <div className="p-6 max-h-[60vh] overflow-y-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredProducts.map(product => (
            <div
              key={product.id}
              className="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between"
            >
              <div>
                <div className="relative aspect-[4/3] rounded-xl overflow-hidden bg-slate-100 mb-3">
                  <img
                    src={product.image}
                    alt={product.name}
                    className="w-full h-full object-cover"
                  />
                  <div className="absolute top-2 right-2 bg-white/90 backdrop-blur-xs text-slate-900 text-[11px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 shadow-xs">
                    <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                    {product.rating}
                  </div>
                </div>

                <span className="text-[11px] font-bold text-[#6e831c] tracking-wider uppercase">
                  {product.category}
                </span>
                <h3 className="text-sm font-bold text-slate-900 mt-0.5 leading-snug">
                  {product.name}
                </h3>
                <p className="text-xs text-slate-500 mt-1 line-clamp-2">
                  {product.description}
                </p>
              </div>

              <div className="flex items-center justify-between mt-4 pt-3 border-t border-slate-100">
                <span className="text-base font-extrabold text-slate-900">
                  ${product.price}
                </span>
                <button
                  onClick={() => onAddToCart(product)}
                  className="px-3 py-1.5 bg-[#6e831c] hover:bg-[#5b6e17] text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                >
                  <Plus className="w-3.5 h-3.5" />
                  Add
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Footer */}
        <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
          <button
            onClick={onClose}
            className="px-6 py-2 bg-slate-900 text-white text-xs font-semibold rounded-xl hover:bg-slate-800 transition-colors"
          >
            Done Shopping
          </button>
        </div>

      </div>
    </div>
  );
}
