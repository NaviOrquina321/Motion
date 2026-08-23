import React from 'react';
import { Star } from 'lucide-react';

export default function Hero({ onAddToCart, onOpenProducts }) {
  const featuredProduct = {
    id: 'wilson-pro-staff-97',
    name: 'Wilson Pro Staff 97',
    category: 'Tennis Racket',
    price: 299,
    image: '/assets/wilson-pro-staff.jpg'
  };

  return (
    <div className="relative min-h-screen w-full flex items-center justify-between px-6 lg:px-16 pt-24 pb-12 overflow-hidden bg-slate-950 text-white select-none">
      {/* Background Image Container */}
      <div
        className="absolute inset-0 bg-cover bg-center bg-no-repeat z-0 scale-105 filter brightness-90"
        style={{ backgroundImage: "url('/assets/landing-bg.jpg')" }}
      >
        {/* Soft dark vignette gradients around edge to highlight text & card */}
        <div className="absolute inset-0 bg-gradient-to-t from-emerald-950/80 via-transparent to-slate-950/40" />
        <div className="absolute inset-0 bg-gradient-to-r from-emerald-950/70 via-transparent to-slate-950/60" />
      </div>

      {/* Hero Content Container */}
      <div className="relative z-20 w-full max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-end min-h-[calc(100vh-120px)] pt-12 pb-6">

        {/* Left Column: Heading, CTA, Stats */}
        <div className="lg:col-span-6 flex flex-col items-start justify-end pb-4 space-y-6">

          {/* Pill Badge */}
          <div className="inline-flex items-center px-4 py-1.5 rounded-full bg-white/90 backdrop-blur-md text-slate-900 text-xs font-medium tracking-wide shadow-lg">
            New Collection
          </div>

          {/* Headline */}
          <h1 className="text-4xl sm:text-6xl lg:text-6xl font-extrabold tracking-tight leading-[1.1] drop-shadow-lg text-white">
            Own The Court.<br />
            Every Match.
          </h1>

          {/* Description */}
          <p className="text-base sm:text-lg text-slate-200/90 max-w-md font-normal leading-relaxed drop-shadow">
            Premium rackets, apparel, shoes, and accessories designed for players who compete with confidence.
          </p>

          {/* Action Buttons */}
          <div className="flex flex-wrap gap-4 pt-2">
            <button
              onClick={onOpenProducts}
              className="px-7 py-3.5 bg-white text-slate-950 font-semibold rounded-xl hover:bg-slate-100 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5 cursor-pointer text-sm"
            >
              Shop Collection
            </button>

            <button
              onClick={onOpenProducts}
              className="px-7 py-3.5 border border-white/40 hover:border-white bg-black/20 hover:bg-white/10 text-white font-medium rounded-xl transition-all backdrop-blur-xs cursor-pointer text-sm"
            >
              Explore Gear
            </button>
          </div>

          {/* Stats Bar Container */}
          <div className="mt-6 w-full max-w-lg grid grid-cols-3 gap-3 p-4 rounded-2xl bg-[#47571b]/85 backdrop-blur-md border border-white/15 shadow-2xl">
            <div className="flex flex-col">
              <span className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">50K+</span>
              <span className="text-xs sm:text-sm text-amber-100/80 font-medium">Players</span>
            </div>

            <div className="flex flex-col border-l border-white/20 pl-3">
              <span className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">200+</span>
              <span className="text-xs sm:text-sm text-amber-100/80 font-medium">Products</span>
            </div>

            <div className="flex flex-col border-l border-white/20 pl-3">
              <div className="flex items-center gap-1">
                <span className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">4.9</span>
                <Star className="w-5 h-5 fill-amber-300 text-amber-300" />
              </div>
              <span className="text-xs sm:text-sm text-amber-100/80 font-medium">Reviews</span>
            </div>
          </div>

        </div>

        {/* Right Floating Product Card (Matching Reference Design) */}
        <div className="lg:col-span-6 lg:col-start-7 flex justify-end pb-4">
          <div className="w-full max-w-sm bg-white rounded-3xl p-3.5 shadow-2xl transition-transform hover:scale-[1.02] duration-300 border border-slate-100">
            {/* Product Image Box */}
            <div className="relative w-full aspect-[4/3] rounded-2xl overflow-hidden bg-slate-100">
              <img
                src={featuredProduct.image}
                alt={featuredProduct.name}
                className="w-full h-full object-cover object-center"
              />
            </div>

            {/* Product Meta */}
            <div className="pt-3 px-1 pb-1">
              <span className="text-xs text-slate-500 font-medium block">
                {featuredProduct.category}
              </span>

              <div className="flex items-center justify-between mt-1">
                <h3 className="text-base font-bold text-slate-900 tracking-tight">
                  {featuredProduct.name}
                </h3>
                <span className="text-base font-bold text-slate-900">
                  ${featuredProduct.price}
                </span>
              </div>

              {/* Add to Cart Button */}
              <button
                onClick={() => onAddToCart(featuredProduct)}
                className="w-full mt-3 py-3 bg-[#6e831c] hover:bg-[#5b6e17] text-white font-semibold rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 cursor-pointer text-sm"
              >
                Add to Cart
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
}
