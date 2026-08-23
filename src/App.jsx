import React, { useState } from 'react';
import Header from './components/Header';
import Hero from './components/Hero';
import CartDrawer from './components/CartDrawer';
import ProductModal from './components/ProductModal';
import AuthModal from './components/AuthModal';

export default function App() {
  const [cartItems, setCartItems] = useState([
    {
      id: 'wilson-pro-staff-97',
      name: 'Wilson Pro Staff 97',
      category: 'Tennis Racket',
      price: 299,
      quantity: 1,
      image: '/assets/wilson-pro-staff.jpg'
    }
  ]);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isProductsOpen, setIsProductsOpen] = useState(false);
  const [isAuthOpen, setIsAuthOpen] = useState(false);
  const [toastMessage, setToastMessage] = useState(null);

  const showToast = (msg) => {
    setToastMessage(msg);
    setTimeout(() => {
      setToastMessage(null);
    }, 3000);
  };

  const handleAddToCart = (product) => {
    setCartItems(prev => {
      const existing = prev.find(item => item.id === product.id);
      if (existing) {
        return prev.map(item =>
          item.id === product.id ? { ...item, quantity: item.quantity + 1 } : item
        );
      }
      return [...prev, { ...product, quantity: 1 }];
    });
    showToast(`Added ${product.name} to cart!`);
  };

  const handleUpdateQuantity = (id, newQty) => {
    if (newQty <= 0) {
      handleRemoveItem(id);
      return;
    }
    setCartItems(prev => prev.map(item => item.id === id ? { ...item, quantity: newQty } : item));
  };

  const handleRemoveItem = (id) => {
    setCartItems(prev => prev.filter(item => item.id !== id));
  };

  const handleCheckout = () => {
    showToast("Redirecting to secure checkout...");
    setTimeout(() => {
      setCartItems([]);
      setIsCartOpen(false);
    }, 1500);
  };

  const totalCartCount = cartItems.reduce((acc, item) => acc + item.quantity, 0);

  return (
    <div className="min-h-screen bg-slate-950 text-white relative selection:bg-lime-500 selection:text-slate-950">
      {/* Toast Notification */}
      {toastMessage && (
        <div className="fixed top-20 right-6 z-50 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-2xl border border-lime-500/40 flex items-center gap-3 animate-bounce">
          <span className="w-2.5 h-2.5 rounded-full bg-lime-400"></span>
          <span className="text-sm font-semibold">{toastMessage}</span>
        </div>
      )}

      {/* Main Header */}
      <Header
        cartCount={totalCartCount}
        onOpenCart={() => setIsCartOpen(true)}
        onOpenAuth={() => setIsAuthOpen(true)}
        onOpenProducts={() => setIsProductsOpen(true)}
      />

      {/* Hero Section */}
      <Hero
        onAddToCart={handleAddToCart}
        onOpenProducts={() => setIsProductsOpen(true)}
      />

      {/* Interactive Modals and Side Drawers */}
      <CartDrawer
        isOpen={isCartOpen}
        onClose={() => setIsCartOpen(false)}
        cartItems={cartItems}
        onUpdateQuantity={handleUpdateQuantity}
        onRemoveItem={handleRemoveItem}
        onCheckout={handleCheckout}
      />

      <ProductModal
        isOpen={isProductsOpen}
        onClose={() => setIsProductsOpen(false)}
        onAddToCart={handleAddToCart}
      />

      <AuthModal
        isOpen={isAuthOpen}
        onClose={() => setIsAuthOpen(false)}
      />
    </div>
  );
}
