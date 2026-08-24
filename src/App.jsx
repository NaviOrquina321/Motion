import React, { useState, useRef, useEffect } from 'react';
import { Player } from '@remotion/player';
import { SupraHeroComposition } from './remotion/SupraHeroComposition';
import { SupraGaugeComposition } from './remotion/SupraGaugeComposition';
import { InteractiveCanvasBackground } from './components/InteractiveCanvasBackground';
import {
  Volume2,
  VolumeX,
  Zap,
  Gauge,
  Flame,
  Palette,
  Sliders,
  Sparkles,
  Cpu,
} from 'lucide-react';

export default function App() {
  // Remotion Dynamic Props
  const [carColor, setCarColor] = useState('#FF0033');
  const [neonColor, setNeonColor] = useState('#00F0FF');
  const [driveMode, setDriveMode] = useState('Sport'); // Normal, Sport, Track
  const [isRevving, setIsRevving] = useState(false);
  const [showFlame, setShowFlame] = useState(true);
  const [hudTitle, setHudTitle] = useState('GR SUPRA MK5');
  const [isMuted, setIsMuted] = useState(false);

  // Mouse coordinate tracking for interactive reactive canvas
  const [mousePos, setMousePos] = useState({ x: 0, y: 0 });

  // Player Ref for Frame Control Scrubber
  const playerRef = useRef(null);

  useEffect(() => {
    const handleMouseMove = (e) => {
      setMousePos({ x: e.clientX, y: e.clientY });
    };
    window.addEventListener('mousemove', handleMouseMove);
    return () => window.removeEventListener('mousemove', handleMouseMove);
  }, []);

  // Web Audio Synthesizer for Engine Sound Simulation
  const audioCtxRef = useRef(null);

  const playEngineRevSound = () => {
    if (isMuted) return;
    try {
      if (!audioCtxRef.current) {
        audioCtxRef.current = new (window.AudioContext || window.webkitAudioContext)();
      }
      const ctx = audioCtxRef.current;
      if (ctx.state === 'suspended') {
        ctx.resume();
      }

      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(110, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(380, ctx.currentTime + 0.3);
      osc.frequency.exponentialRampToValueAtTime(120, ctx.currentTime + 0.8);

      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.linearRampToValueAtTime(0, ctx.currentTime + 0.85);

      osc.connect(gain);
      gain.connect(ctx.destination);

      osc.start();
      osc.stop(ctx.currentTime + 0.85);
    } catch (e) {
      console.log('Audio init skipped', e);
    }
  };

  const handleRevEngine = () => {
    setIsRevving(true);
    playEngineRevSound();
    setTimeout(() => setIsRevving(false), 900);
  };

  return (
    <div className="relative min-h-screen bg-[#0A0B0E] text-white font-sans overflow-x-hidden selection:bg-red-500 selection:text-white">
      {/* Interactive Reactive Particle Background */}
      <InteractiveCanvasBackground
        mousePos={mousePos}
        neonColor={neonColor}
        driveMode={driveMode}
      />

      {/* Navigation Header */}
      <header className="relative z-20 flex items-center justify-between px-6 py-4 border-b border-white/10 bg-black/40 backdrop-blur-xl sticky top-0">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-red-600 rounded-lg shadow-lg shadow-red-600/40">
            <Zap className="w-5 h-5 text-white animate-pulse" />
          </div>
          <div>
            <div className="text-xl font-black italic tracking-wider font-display bg-gradient-to-r from-white via-gray-200 to-red-500 bg-clip-text text-transparent">
              SUPRA<span className="text-red-500">MOTION</span>
            </div>
            <div className="text-[10px] font-mono tracking-widest text-gray-400">
              PROGRAMMATIC MOTION GRAPHICS
            </div>
          </div>
        </div>

        {/* Quick Drive Mode Selector */}
        <div className="hidden md:flex items-center gap-2 p-1 bg-white/5 border border-white/10 rounded-xl font-mono text-xs">
          {['Normal', 'Sport', 'Track'].map((mode) => (
            <button
              key={mode}
              onClick={() => setDriveMode(mode)}
              className={`px-4 py-1.5 rounded-lg transition-all duration-200 font-semibold ${
                driveMode === mode
                  ? 'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg shadow-red-600/30'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`}
            >
              {mode.toUpperCase()}
            </button>
          ))}
        </div>

        {/* Sound Toggle */}
        <button
          onClick={() => setIsMuted(!isMuted)}
          className="p-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-gray-300 hover:text-white transition-all flex items-center gap-2 text-xs font-mono"
        >
          {isMuted ? <VolumeX className="w-4 h-4 text-red-400" /> : <Volume2 className="w-4 h-4 text-emerald-400" />}
          <span className="hidden sm:inline">{isMuted ? 'MUTED' : 'AUDIO ACTIVE'}</span>
        </button>
      </header>

      {/* Main Container */}
      <main className="relative z-10 max-w-7xl mx-auto px-4 py-8 flex flex-col gap-12">
        {/* HERO SECTION: Remotion Video Player */}
        <section className="flex flex-col gap-4">
          <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
              <div className="flex items-center gap-2 text-red-500 font-mono text-xs font-semibold uppercase tracking-widest mb-1">
                <Sparkles className="w-4 h-4" /> Live Reactive Canvas
              </div>
              <h1 className="text-3xl md:text-5xl font-black font-display tracking-tight">
                TOYOTA GR SUPRA <span className="text-red-500">MK5</span>
              </h1>
              <p className="text-gray-400 text-sm max-w-xl mt-1">
                Rendered live in React using <span className="text-cyan-400 font-mono">Remotion Player</span>. Interact with controls below to tune car parameters in real-time.
              </p>
            </div>

            <button
              onClick={handleRevEngine}
              className="group relative inline-flex items-center justify-center px-8 py-3.5 text-base font-bold font-mono text-white transition-all duration-200 bg-red-600 rounded-xl overflow-hidden shadow-lg shadow-red-600/40 hover:bg-red-500 active:scale-95"
            >
              <Flame className="w-5 h-5 mr-2 animate-bounce text-yellow-300" />
              REV ENGINE & TURBO
            </button>
          </div>

          {/* Remotion Player Wrapper */}
          <div className="relative w-full aspect-video bg-card rounded-2xl border border-white/10 shadow-2xl overflow-hidden group">
            <Player
              ref={playerRef}
              component={SupraHeroComposition}
              durationInFrames={180}
              compositionWidth={1280}
              compositionHeight={720}
              fps={30}
              loop
              autoPlay
              style={{
                width: '100%',
                height: '100%',
              }}
              inputProps={{
                carColor,
                neonColor,
                driveMode,
                isRevving,
                showFlame,
                hudTitle,
              }}
            />

            {/* Floating Live Indicator Badge */}
            <div className="absolute bottom-4 left-4 flex items-center gap-2 bg-black/70 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/10 text-xs font-mono">
              <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping" />
              <span className="text-gray-300">REMOTION ENGINE READY</span>
            </div>
          </div>
        </section>

        {/* CUSTOMIZER & PARAMETER STUDIO */}
        <section className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Customizer Panel */}
          <div className="lg:col-span-2 bg-card/80 backdrop-blur-md border border-white/10 rounded-2xl p-6 flex flex-col gap-6 shadow-xl">
            <div className="flex items-center justify-between border-b border-white/10 pb-4">
              <div className="flex items-center gap-2 font-display font-bold text-lg text-white">
                <Sliders className="w-5 h-5 text-red-500" />
                REMOTION PARAMETER CUSTOMIZER
              </div>
              <span className="text-xs font-mono bg-white/5 px-2.5 py-1 rounded text-cyan-400 border border-white/10">
                PROPS STATE ACTIVE
              </span>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Car Body Color */}
              <div className="flex flex-col gap-3">
                <label className="text-xs font-mono text-gray-400 flex items-center gap-2">
                  <Palette className="w-4 h-4 text-red-400" />
                  CAR PAINT COLOR:
                </label>
                <div className="flex items-center gap-3">
                  {[
                    { name: 'Prominence Red', hex: '#FF0033' },
                    { name: 'Nitro Yellow', hex: '#FFCC00' },
                    { name: 'Phantom Gray', hex: '#3A3F4D' },
                    { name: 'Stratosphere Blue', hex: '#0055FF' },
                    { name: 'Absolute White', hex: '#FFFFFF' },
                  ].map((color) => (
                    <button
                      key={color.hex}
                      onClick={() => setCarColor(color.hex)}
                      title={color.name}
                      style={{ backgroundColor: color.hex }}
                      className={`w-9 h-9 rounded-full border-2 transition-transform ${
                        carColor === color.hex ? 'scale-125 border-white shadow-lg' : 'border-transparent opacity-80 hover:opacity-100'
                      }`}
                    />
                  ))}
                </div>
              </div>

              {/* Underglow Neon Color */}
              <div className="flex flex-col gap-3">
                <label className="text-xs font-mono text-gray-400 flex items-center gap-2">
                  <Sparkles className="w-4 h-4 text-cyan-400" />
                  NEON UNDERGLOW:
                </label>
                <div className="flex items-center gap-3">
                  {[
                    { name: 'Cyber Cyan', hex: '#00F0FF' },
                    { name: 'Neon Red', hex: '#FF0033' },
                    { name: 'Electric Purple', hex: '#A855F7' },
                    { name: 'Acid Green', hex: '#22C55E' },
                  ].map((color) => (
                    <button
                      key={color.hex}
                      onClick={() => setNeonColor(color.hex)}
                      title={color.name}
                      style={{ backgroundColor: color.hex }}
                      className={`w-9 h-9 rounded-full border-2 transition-transform ${
                        neonColor === color.hex ? 'scale-125 border-white shadow-lg' : 'border-transparent opacity-80 hover:opacity-100'
                      }`}
                    />
                  ))}
                </div>
              </div>

              {/* HUD Badge Text Input */}
              <div className="flex flex-col gap-2">
                <label className="text-xs font-mono text-gray-400 flex items-center gap-2">
                  <Cpu className="w-4 h-4 text-yellow-400" />
                  HUD DISPLAY TITLE:
                </label>
                <input
                  type="text"
                  value={hudTitle}
                  onChange={(e) => setHudTitle(e.target.value)}
                  className="bg-black/60 border border-white/15 rounded-xl px-4 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-red-500"
                  maxLength={18}
                />
              </div>

              {/* Flame Exhaust Toggle */}
              <div className="flex flex-col gap-2">
                <label className="text-xs font-mono text-gray-400 flex items-center gap-2">
                  <Flame className="w-4 h-4 text-orange-400" />
                  TURBO EXHAUST FLAMES:
                </label>
                <button
                  onClick={() => setShowFlame(!showFlame)}
                  className={`px-4 py-2.5 rounded-xl border font-mono text-xs font-bold transition-all flex items-center justify-between ${
                    showFlame
                      ? 'bg-orange-500/20 border-orange-500 text-orange-400'
                      : 'bg-white/5 border-white/10 text-gray-500'
                  }`}
                >
                  <span>{showFlame ? 'ENABLED (AFTERBURNER)' : 'DISABLED'}</span>
                  <Flame className={`w-4 h-4 ${showFlame ? 'text-orange-400 animate-pulse' : 'text-gray-600'}`} />
                </button>
              </div>
            </div>
          </div>

          {/* Secondary Remotion Gauge Composition Showcase */}
          <div className="bg-card/80 backdrop-blur-md border border-white/10 rounded-2xl p-6 flex flex-col gap-4 shadow-xl">
            <div className="flex items-center justify-between border-b border-white/10 pb-3">
              <div className="flex items-center gap-2 font-display font-bold text-sm text-white">
                <Gauge className="w-4 h-4 text-cyan-400" />
                LIVE METRICS GAUGE
              </div>
              <span className="text-[10px] font-mono text-gray-400">30 FPS REMOTION</span>
            </div>

            <div className="w-full aspect-[16/9] bg-black/60 rounded-xl overflow-hidden border border-white/10">
              <Player
                component={SupraGaugeComposition}
                durationInFrames={120}
                compositionWidth={800}
                compositionHeight={450}
                fps={30}
                loop
                autoPlay
                style={{ width: '100%', height: '100%' }}
                inputProps={{
                  hp: driveMode === 'Track' ? 450 : driveMode === 'Sport' ? 382 : 335,
                  torque: driveMode === 'Track' ? 580 : 500,
                  accentColor: carColor,
                }}
              />
            </div>

            <div className="text-xs text-gray-400 font-mono flex items-center justify-between">
              <span>B58 3.0L TURBO INLINE-6</span>
              <span className="text-emerald-400">SYNCHRONIZED</span>
            </div>
          </div>
        </section>

        {/* SPECIFICATIONS & FEATURES GRID */}
        <section className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-card/50 border border-white/10 rounded-2xl p-6 flex flex-col gap-2 hover:border-red-500/50 transition-all">
            <div className="text-xs font-mono text-gray-400">ENGINE & POWER</div>
            <div className="text-2xl font-black font-display text-white">3.0L INLINE 6 TURBO</div>
            <p className="text-xs text-gray-400 leading-relaxed mt-1">
              Twin-scroll turbocharger with 382 HP and 368 lb-ft torque delivering instantaneous throttle response.
            </p>
          </div>

          <div className="bg-card/50 border border-white/10 rounded-2xl p-6 flex flex-col gap-2 hover:border-cyan-500/50 transition-all">
            <div className="text-xs font-mono text-gray-400">WEIGHT DISTRIBUTION</div>
            <div className="text-2xl font-black font-display text-white">50 : 50 PERFECT BALANCE</div>
            <p className="text-xs text-gray-400 leading-relaxed mt-1">
              Low center of gravity with adaptive variable suspension tuned for track precision.
            </p>
          </div>

          <div className="bg-card/50 border border-white/10 rounded-2xl p-6 flex flex-col gap-2 hover:border-yellow-500/50 transition-all">
            <div className="text-xs font-mono text-gray-400">TRANSMISSION</div>
            <div className="text-2xl font-black font-display text-white">8-SPEED SPORT AUTO</div>
            <p className="text-xs text-gray-400 leading-relaxed mt-1">
              Paddle-shift manual mode with active launch control and rear wheel drive differential.
            </p>
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer className="relative z-10 border-t border-white/10 py-6 px-6 mt-12 text-center text-xs font-mono text-gray-500 flex flex-col sm:flex-row items-center justify-between gap-4 max-w-7xl mx-auto">
        <div>TOYOTA SUPRA REMOTION MOTION GRAPHICS EXPERIMENT</div>
        <div className="text-gray-400">POWERED BY REACT & REMOTION.DEV</div>
      </footer>
    </div>
  );
}
