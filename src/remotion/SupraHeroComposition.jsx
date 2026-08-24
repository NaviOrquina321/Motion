import React from 'react';
import { interpolate, spring, useCurrentFrame, useVideoConfig } from 'remotion';

export const SupraHeroComposition = ({
  carColor = '#FF0033',
  neonColor = '#00F0FF',
  driveMode = 'Sport',
  isRevving = false,
  showFlame = true,
  hudTitle = 'GR SUPRA MK5',
}) => {
  const frame = useCurrentFrame();
  const { fps, durationInFrames, width, height } = useVideoConfig();

  // Speed multiplier based on drive mode
  const speedFactor = driveMode === 'Track' ? 2.5 : driveMode === 'Sport' ? 1.8 : 1.0;

  // Rev pulse effect
  const revPulse = isRevving ? Math.sin(frame * 0.8) * 15 : 0;

  // Speedometer calculation
  const targetSpeed = driveMode === 'Track' ? 285 : driveMode === 'Sport' ? 220 : 120;
  const currentSpeed = Math.min(
    targetSpeed,
    Math.round(interpolate(frame, [0, 60, durationInFrames], [0, targetSpeed, targetSpeed - 5], {
      extrapolateRight: 'clamp',
    }) + (isRevving ? Math.sin(frame) * 18 : 0))
  );

  // Tachometer RPM calculation
  const rpm = Math.min(
    7500,
    Math.round(interpolate(frame % 40, [0, 20, 40], [2500, 6800, 3200]) + (isRevving ? 2200 : 0))
  );

  // Animated background grid offset
  const gridOffsetY = (frame * 12 * speedFactor) % 60;
  const speedLineOffset = (frame * 35 * speedFactor) % 1000;

  // Spring animations for badge text
  const badgeSpring = spring({
    frame,
    fps,
    config: { damping: 12 },
  });

  return (
    <div
      style={{
        width: '100%',
        height: '100%',
        backgroundColor: '#07080B',
        fontFamily: 'Orbitron, sans-serif',
        overflow: 'hidden',
        position: 'relative',
      }}
      className="flex flex-col items-center justify-center select-none"
    >
      {/* Dynamic Grid Background */}
      <div
        style={{
          position: 'absolute',
          inset: '-20%',
          backgroundImage: `
            linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px)
          `,
          backgroundSize: '60px 60px',
          transform: `perspective(600px) rotateX(60deg) translateY(${gridOffsetY}px)`,
          transformOrigin: '50% 100%',
        }}
      />

      {/* Speed Lines */}
      {Array.from({ length: 18 }).map((_, i) => {
        const xPos = (i * 120) % 1920;
        const lineY = (speedLineOffset + i * 80) % 1080;
        const opacity = (i % 3 === 0 ? 0.3 : 0.15);
        return (
          <div
            key={i}
            style={{
              position: 'absolute',
              left: `${xPos}px`,
              top: `${lineY}px`,
              width: '2px',
              height: `${80 * speedFactor}px`,
              backgroundColor: neonColor,
              boxShadow: `0 0 12px ${neonColor}`,
              opacity,
            }}
          />
        );
      })}

      {/* Center Ambient Light Glow */}
      <div
        style={{
          position: 'absolute',
          width: '600px',
          height: '600px',
          borderRadius: '50%',
          background: `radial-gradient(circle, ${carColor}44 0%, ${neonColor}22 40%, transparent 70%)`,
          filter: 'blur(60px)',
          transform: `scale(${1 + revPulse * 0.02})`,
        }}
      />

      {/* Car Silhouette / SVG Visual */}
      <div
        style={{
          position: 'relative',
          zIndex: 10,
          transform: `translateY(${revPulse}px) scale(${1 + revPulse * 0.005})`,
          transition: 'transform 0.05s ease-out',
        }}
        className="flex flex-col items-center justify-center"
      >
        <svg
          width="800"
          height="320"
          viewBox="0 0 800 320"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          className="drop-shadow-[0_20px_40px_rgba(0,0,0,0.8)]"
        >
          <defs>
            <linearGradient id="bodyGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor={carColor} />
              <stop offset="100%" stopColor="#111319" />
            </linearGradient>
            <linearGradient id="glassGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#00F0FF" stopOpacity="0.6" />
              <stop offset="100%" stopColor="#050810" stopOpacity="0.9" />
            </linearGradient>
            <filter id="neonGlow">
              <feGaussianBlur stdDeviation="6" result="coloredBlur" />
              <feMerge>
                <feMergeNode in="coloredBlur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
          </defs>

          {/* Underglow Neon Light */}
          <ellipse
            cx="400"
            cy="270"
            rx="340"
            ry="25"
            fill={neonColor}
            opacity={0.75 + Math.sin(frame * 0.2) * 0.15}
            filter="url(#neonGlow)"
          />

          {/* Exhaust Flame FX */}
          {showFlame && (isRevving || driveMode === 'Track') && (
            <g transform="translate(680, 220)">
              <polygon
                points="0,0 60,-15 90,0 60,15"
                fill="#FF3300"
                opacity={0.8 + Math.random() * 0.2}
                filter="url(#neonGlow)"
              />
              <polygon
                points="0,0 40,-8 60,0 40,8"
                fill="#FFCC00"
                opacity={0.9}
              />
              <circle cx="15" cy="0" r="10" fill="#00F0FF" opacity="0.9" />
            </g>
          )}

          {/* Car Body Contour */}
          <path
            d="M80 230 C 120 230, 160 210, 220 180 C 280 150, 360 110, 470 110 C 580 110, 640 160, 710 190 C 750 205, 760 230, 760 230 L 780 245 C 780 245, 760 260, 700 260 L 120 260 C 90 260, 70 245, 80 230 Z"
            fill="url(#bodyGradient)"
            stroke={carColor}
            strokeWidth="3"
          />

          {/* Roof and Cabin Line */}
          <path
            d="M260 165 C 320 120, 420 115, 520 135 C 570 145, 600 170, 610 180 Z"
            fill="url(#glassGradient)"
            stroke="#2A303C"
            strokeWidth="2"
          />

          {/* Supra Rear Wing / Spoiler Accent */}
          <path
            d="M710 175 L 775 165 L 780 178 L 720 188 Z"
            fill={carColor}
            filter="url(#neonGlow)"
          />

          {/* Aggressive Headlight LED */}
          <path
            d="M110 210 L 160 205 L 140 215 Z"
            fill="#FFFFFF"
            filter="url(#neonGlow)"
          />
          <path
            d="M100 215 L 170 210"
            stroke={neonColor}
            strokeWidth="4"
            strokeLinecap="round"
            filter="url(#neonGlow)"
          />

          {/* Tail Light Strip */}
          <path
            d="M710 200 L 760 210 L 755 218 L 705 208 Z"
            fill="#FF0033"
            filter="url(#neonGlow)"
          />

          {/* Wheels */}
          <g transform="translate(190, 240)">
            <circle cx="0" cy="0" r="42" fill="#0D0E12" stroke="#3A3F4D" strokeWidth="6" />
            <circle cx="0" cy="0" r="28" fill="#181B22" stroke={neonColor} strokeWidth="2" />
            {/* Rotating Wheel Spokes */}
            <g transform={`rotate(${(frame * 25 * speedFactor) % 360})`}>
              <line x1="-22" y1="0" x2="22" y2="0" stroke="#E5E7EB" strokeWidth="4" />
              <line x1="0" y1="-22" x2="0" y2="22" stroke="#E5E7EB" strokeWidth="4" />
            </g>
          </g>

          <g transform="translate(620, 240)">
            <circle cx="0" cy="0" r="42" fill="#0D0E12" stroke="#3A3F4D" strokeWidth="6" />
            <circle cx="0" cy="0" r="28" fill="#181B22" stroke={neonColor} strokeWidth="2" />
            <g transform={`rotate(${(frame * 25 * speedFactor) % 360})`}>
              <line x1="-22" y1="0" x2="22" y2="0" stroke="#E5E7EB" strokeWidth="4" />
              <line x1="0" y1="-22" x2="0" y2="22" stroke="#E5E7EB" strokeWidth="4" />
            </g>
          </g>
        </svg>

        {/* Dynamic Title / Badge Overlay */}
        <div
          style={{
            transform: `scale(${badgeSpring})`,
            marginTop: '-10px',
          }}
          className="text-center"
        >
          <div className="text-xs tracking-[0.4em] text-gray-400 font-mono mb-1">
            TOYOTA GAZOO RACING
          </div>
          <h1
            style={{
              textShadow: `0 0 20px ${carColor}, 0 0 40px ${neonColor}`,
            }}
            className="text-5xl md:text-6xl font-black italic tracking-widest text-white uppercase"
          >
            {hudTitle}
          </h1>
        </div>
      </div>

      {/* Futuristic HUD Elements Overlay */}
      <div className="absolute top-8 left-8 flex flex-col gap-2 bg-black/60 backdrop-blur-md p-4 rounded-xl border border-white/10 text-xs font-mono">
        <div className="flex items-center gap-3">
          <span className="w-2 h-2 rounded-full bg-red-500 animate-ping" />
          <span className="text-gray-400">DRIVE MODE:</span>
          <span className="text-red-400 font-bold tracking-wider">{driveMode.toUpperCase()}</span>
        </div>
        <div className="flex items-center gap-3">
          <span className="text-gray-400">TURBO PRESSURE:</span>
          <span className="text-cyan-400 font-bold">{(1.2 + (currentSpeed / 200) * 0.8).toFixed(2)} BAR</span>
        </div>
        <div className="flex items-center gap-3">
          <span className="text-gray-400">FRAME TIME:</span>
          <span className="text-yellow-400">{frame} / {durationInFrames}</span>
        </div>
      </div>

      {/* Speedometer & Tachometer Right HUD */}
      <div className="absolute top-8 right-8 flex items-center gap-6 bg-black/60 backdrop-blur-md p-4 rounded-xl border border-white/10 font-mono">
        <div className="text-right">
          <div className="text-xs text-gray-400">SPEED</div>
          <div className="text-4xl font-black tracking-tight text-white">
            {currentSpeed} <span className="text-xs font-normal text-gray-400">KM/H</span>
          </div>
        </div>
        <div className="w-[1px] h-10 bg-white/10" />
        <div className="text-right">
          <div className="text-xs text-gray-400">ENGINE RPM</div>
          <div className="text-3xl font-bold tracking-tight text-cyan-400">
            {rpm}
          </div>
        </div>
      </div>

      {/* Bottom Audio Visualizer Bar */}
      <div className="absolute bottom-6 w-3/4 flex items-end justify-center gap-1.5 h-12">
        {Array.from({ length: 32 }).map((_, idx) => {
          const heightPct = Math.min(
            100,
            Math.max(15, Math.sin(frame * 0.15 + idx * 0.3) * 50 + (isRevving ? 40 : 25) + (idx % 4) * 8)
          );
          return (
            <div
              key={idx}
              style={{
                height: `${heightPct}%`,
                backgroundColor: idx > 24 ? '#FF0033' : neonColor,
                boxShadow: `0 0 10px ${idx > 24 ? '#FF0033' : neonColor}`,
              }}
              className="w-2 rounded-t-sm transition-all duration-75"
            />
          );
        })}
      </div>
    </div>
  );
};
