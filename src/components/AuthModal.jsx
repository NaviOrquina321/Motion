import React, { useState } from 'react';
import { X, Lock, Mail, ArrowRight } from 'lucide-react';

export default function AuthModal({ isOpen, onClose }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitted, setSubmitted] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = (e) => {
    e.preventDefault();
    if (email && password) {
      setSubmitted(true);
      setTimeout(() => {
        setSubmitted(false);
        onClose();
      }, 1500);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"
        onClick={onClose}
      />

      {/* Modal Card */}
      <div className="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden z-10 p-8 text-slate-900">
        <button
          onClick={onClose}
          className="absolute top-6 right-6 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors"
        >
          <X className="w-5 h-5" />
        </button>

        <div className="mb-6">
          <span className="text-3xl font-bold tracking-wide text-[#6e831c]" style={{ fontFamily: "'Caveat', cursive" }}>
            Tennis
          </span>
          <h2 className="text-2xl font-bold text-slate-900 mt-2">Welcome Back</h2>
          <p className="text-xs text-slate-500 mt-1">
            Sign in to access your pro orders, court reservations & exclusive gear perks.
          </p>
        </div>

        {submitted ? (
          <div className="py-8 text-center bg-emerald-50 rounded-2xl border border-emerald-100 p-6">
            <div className="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center mx-auto mb-3 font-bold text-xl">
              ✓
            </div>
            <h3 className="text-base font-bold text-emerald-900">Signed In Successfully!</h3>
            <p className="text-xs text-emerald-700 mt-1">Welcome back to Tennis Store.</p>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
              <div className="relative">
                <Mail className="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
                <input
                  type="email"
                  required
                  placeholder="player@tennis.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#6e831c] focus:border-transparent transition-all"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">Password</label>
              <div className="relative">
                <Lock className="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
                <input
                  type="password"
                  required
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#6e831c] focus:border-transparent transition-all"
                />
              </div>
            </div>

            <div className="flex items-center justify-between text-xs pt-1">
              <label className="flex items-center gap-2 cursor-pointer text-slate-600">
                <input type="checkbox" className="rounded border-slate-300 text-[#6e831c] focus:ring-[#6e831c]" />
                <span>Remember me</span>
              </label>
              <a href="#" className="font-semibold text-[#6e831c] hover:underline">Forgot password?</a>
            </div>

            <button
              type="submit"
              className="w-full py-3.5 bg-[#6e831c] hover:bg-[#5b6e17] text-white font-bold rounded-xl transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer mt-2"
            >
              <span>Sign In</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
