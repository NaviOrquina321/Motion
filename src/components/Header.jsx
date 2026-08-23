import React from 'react';
import { ShoppingBag, Menu, X } from 'lucide-react';

export default function Header({ cartCount, onOpenCart, onOpenAuth, onOpenProducts }) {
  const [mobileMenuOpen, setMobileMenuOpen] = React.useState(false);

  return (
    <header className="absolute top-0 left-0 right-0 z-40 px-6 lg:px-12 py-6 flex items-center justify-between text-white">
      {/* Brand Logo */}
      <a href="#" className="flex items-center gap-2 group">
        <span
          className="text-4xl sm:text-5xl font-bold tracking-wide transition-transform group-hover:scale-105 drop-shadow-md"
          style={{ fontFamily: "'Caveat', cursive" }}
        >
          Tennis
        </span>
      </a>

      {/* Desktop Navigation Links */}
      <nav className="hidden md:flex items-center space-x-8 text-sm font-medium tracking-wide">
        <button
          onClick={onOpenProducts}
          className="hover:text-amber-200 transition-colors cursor-pointer text-white/90 drop-shadow"
        >
          Products
        </button>
        <a
          href="#about"
          className="hover:text-amber-200 transition-colors text-white/90 drop-shadow"
        >
          About us
        </a>
        <a
          href="#solutions"
          className="hover:text-amber-200 transition-colors text-white/90 drop-shadow"
        >
          Solutions
        </a>
        <a
          href="#contact"
          className="hover:text-amber-200 transition-colors text-white/90 drop-shadow"
        >
          Contact
        </a>
      </nav>

      {/* Desktop Actions */}
      <div className="hidden md:flex items-center space-x-4">
        <button
          onClick={onOpenAuth}
          className="px-5 py-2 text-sm font-medium rounded-full border border-white/40 hover:border-white hover:bg-white/10 transition-all backdrop-blur-xs cursor-pointer"
        >
          Sign in
        </button>

        <button
          onClick={onOpenProducts}
          className="px-5 py-2 text-sm font-semibold text-slate-900 bg-white rounded-full hover:bg-slate-100 transition-all shadow-md hover:shadow-lg cursor-pointer"
        >
          Store
        </button>

        <button
          onClick={onOpenCart}
          className="relative p-2 rounded-full border border-white/30 hover:bg-white/10 transition-colors cursor-pointer"
          aria-label="Shopping Cart"
        >
          <ShoppingBag className="w-5 h-5 text-white" />
          {cartCount > 0 && (
            <span className="absolute -top-1 -right-1 bg-lime-500 text-slate-950 text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center shadow-md">
              {cartCount}
            </span>
          )}
        </button>
      </div>

      {/* Mobile Menu Toggle */}
      <div className="flex items-center gap-3 md:hidden">
        <button
          onClick={onOpenCart}
          className="relative p-2 rounded-full border border-white/30 hover:bg-white/10"
        >
          <ShoppingBag className="w-5 h-5 text-white" />
          {cartCount > 0 && (
            <span className="absolute -top-1 -right-1 bg-lime-500 text-slate-950 text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
              {cartCount}
            </span>
          )}
        </button>

        <button
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          className="p-2 text-white hover:bg-white/10 rounded-lg transition-colors"
        >
          {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {/* Mobile Menu Drawer */}
      {mobileMenuOpen && (
        <div className="absolute top-full left-0 right-0 bg-slate-900/95 backdrop-blur-md p-6 flex flex-col space-y-4 md:hidden border-b border-white/10 shadow-2xl z-50">
          <button
            onClick={() => { onOpenProducts(); setMobileMenuOpen(false); }}
            className="text-left text-lg font-medium text-white hover:text-amber-200 py-1"
          >
            Products
          </button>
          <a
            href="#about"
            onClick={() => setMobileMenuOpen(false)}
            className="text-lg font-medium text-white hover:text-amber-200 py-1"
          >
            About us
          </a>
          <a
            href="#solutions"
            onClick={() => setMobileMenuOpen(false)}
            className="text-lg font-medium text-white hover:text-amber-200 py-1"
          >
            Solutions
          </a>
          <a
            href="#contact"
            onClick={() => setMobileMenuOpen(false)}
            className="text-lg font-medium text-white hover:text-amber-200 py-1"
          >
            Contact
          </a>
          <div className="pt-4 flex flex-col gap-3 border-t border-white/10">
            <button
              onClick={() => { onOpenAuth(); setMobileMenuOpen(false); }}
              className="w-full py-2.5 text-center text-sm font-medium rounded-full border border-white/40 hover:bg-white/10 text-white"
            >
              Sign in
            </button>
            <button
              onClick={() => { onOpenProducts(); setMobileMenuOpen(false); }}
              className="w-full py-2.5 text-center text-sm font-semibold text-slate-900 bg-white rounded-full hover:bg-slate-100"
            >
              Store
            </button>
          </div>
        </div>
      )}
    </header>
  );
}
