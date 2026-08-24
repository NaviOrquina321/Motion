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
  const { fps, durationInFrames } = useVideoConfig();

  // Speed factor based on drive mode
  const speedFactor = driveMode === 'Track' ? 2.5 : driveMode === 'Sport' ? 1.8 : 1.0;

  // Rev pulse effect
  const revPulse = isRevving ? Math.sin(frame * 0.8) * 12 : 0;

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
          width: '700px',
          height: '700px',
          borderRadius: '50%',
          background: `radial-gradient(circle, ${carColor}55 0%, ${neonColor}22 45%, transparent 70%)`,
          filter: 'blur(70px)',
          transform: `scale(${1 + revPulse * 0.02})`,
        }}
      />

      {/* DETAILED TOYOTA GR SUPRA GRAPHIC */}
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
          width="900"
          height="380"
          viewBox="0 0 900 380"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          className="drop-shadow-[0_25px_50px_rgba(0,0,0,0.9)]"
        >
          <defs>
            <linearGradient id="bodyPaint" x1="0%" y1="0%" x2="100%" y2="80%">
              <stop offset="0%" stopColor="#FFFFFF" stopOpacity="0.3" />
              <stop offset="20%" stopColor={carColor} />
              <stop offset="75%" stopColor={carColor} />
              <stop offset="100%" stopColor="#0B0C10" />
            </linearGradient>

            <linearGradient id="roofShadow" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stopColor="#0B0C10" />
              <stop offset="100%" stopColor="#1A1D24" />
            </linearGradient>

            <linearGradient id="glassTint" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#00F0FF" stopOpacity="0.4" />
              <stop offset="40%" stopColor="#0F172A" stopOpacity="0.8" />
              <stop offset="100%" stopColor="#020617" stopOpacity="0.95" />
            </linearGradient>

            <linearGradient id="rimMetal" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#F3F4F6" />
              <stop offset="50%" stopColor="#6B7280" />
              <stop offset="100%" stopColor="#111827" />
            </linearGradient>

            <filter id="neonGlow">
              <feGaussianBlur stdDeviation="8" result="coloredBlur" />
              <feMerge>
                <feMergeNode in="coloredBlur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
          </defs>

          {/* Underglow Neon Light */}
          <ellipse
            cx="450"
            cy="315"
            rx="400"
            ry="28"
            fill={neonColor}
            opacity={0.85 + Math.sin(frame * 0.2) * 0.15}
            filter="url(#neonGlow)"
          />

          {/* Exhaust Flame FX */}
          {showFlame && (isRevving || driveMode === 'Track') && (
            <g transform="translate(805, 272)">
              <polygon
                points="0,0 75,-20 110,0 75,20"
                fill="#FF3300"
                opacity={0.85 + Math.random() * 0.15}
                filter="url(#neonGlow)"
              />
              <polygon
                points="0,0 50,-10 75,0 50,10"
                fill="#FFCC00"
                opacity={0.95}
              />
              <circle cx="20" cy="0" r="12" fill="#00F0FF" opacity="0.95" />
            </g>
          )}

          {/* Supra Main Body Base Shadow */}
          <path
            d="M 60 305 L 840 305 C 850 305, 855 295, 845 285 L 800 240 C 760 210, 710 180, 610 160 C 510 120, 420 120, 310 150 C 230 170, 150 210, 100 245 L 60 275 C 50 285, 50 305, 60 305 Z"
            fill="#050608"
          />

          {/* Supra Sculpted Side Panel Body */}
          <path
            d="M 80 295 C 100 280, 130 255, 180 230 C 240 200, 320 160, 440 150 C 560 140, 650 175, 730 210 C 780 230, 820 255, 835 275 L 820 295 H 80 Z"
            fill="url(#bodyPaint)"
            stroke={carColor}
            strokeWidth="2.5"
          />

          {/* Double Bubble Roof & A-Pillars */}
          <path
            d="M 280 175 C 330 130, 420 125, 520 140 C 580 150, 620 175, 640 195 C 610 185, 520 170, 420 170 C 340 170, 300 180, 280 175 Z"
            fill="url(#roofShadow)"
            stroke="#27272A"
            strokeWidth="2"
          />

          {/* Side Glass / Windshield */}
          <path
            d="M 295 178 C 350 140, 430 135, 515 148 C 560 158, 595 178, 610 190 C 540 180, 410 180, 295 178 Z"
            fill="url(#glassTint)"
            stroke="#38BDF8"
            strokeWidth="1.5"
          />

          {/* Front Bumper & Low Splitter */}
          <path
            d="M 60 295 C 50 295, 45 285, 55 275 L 110 245 C 130 235, 160 230, 190 230 L 190 295 Z"
            fill="#111827"
            stroke={neonColor}
            strokeWidth="2"
            filter="url(#neonGlow)"
          />

          {/* Aggressive Front LED Headlights */}
          <g>
            <path
              d="M 120 235 L 180 228 L 165 242 L 110 245 Z"
              fill="#FFFFFF"
              filter="url(#neonGlow)"
            />
            <path
              d="M 105 245 L 185 235"
              stroke={neonColor}
              strokeWidth="4"
              strokeLinecap="round"
              filter="url(#neonGlow)"
            />
            {/* DRL LED Strip */}
            <path
              d="M 115 248 L 160 242"
              stroke="#FFFFFF"
              strokeWidth="2"
              filter="url(#neonGlow)"
            />
          </g>

          {/* Characteristic Supra Side Air Intake Vent */}
          <path
            d="M 580 220 C 590 200, 605 190, 615 190 C 605 210, 595 235, 580 250 Z"
            fill="#090A0F"
            stroke={carColor}
            strokeWidth="2"
          />

          {/* Rear Fender Flare & Ducktail Spoiler */}
          <path
            d="M 720 215 C 750 200, 785 190, 810 190 C 825 190, 835 195, 830 210 C 810 230, 770 250, 720 250 Z"
            fill={carColor}
            stroke="#F43F5E"
            strokeWidth="1.5"
          />

          {/* Rear LED Tail Lights */}
          <path
            d="M 780 235 L 830 245 L 825 255 L 775 245 Z"
            fill="#FF0033"
            filter="url(#neonGlow)"
          />

          {/* Detailed Front Wheel & Brembo Caliper */}
          <g transform="translate(210, 280)">
            {/* Outer Tire */}
            <circle cx="0" cy="0" r="48" fill="#0A0C10" stroke="#1F2937" strokeWidth="8" />
            {/* Brake Rotor */}
            <circle cx="0" cy="0" r="34" fill="#374151" stroke="#9CA3AF" strokeWidth="2" />
            {/* Red Brake Caliper */}
            <path d="M -22 -20 C -15 -32, 5 -32, 15 -25 L 8 -12 C 0 -18, -10 -18, -15 -10 Z" fill="#EF4444" />
            {/* Rotating 5-Spoke Alloy Rim */}
            <g transform={`rotate(${(frame * 25 * speedFactor) % 360})`}>
              <circle cx="0" cy="0" r="28" fill="none" stroke="url(#rimMetal)" strokeWidth="4" />
              {Array.from({ length: 5 }).map((_, idx) => (
                <line
                  key={idx}
                  x1="0"
                  y1="0"
                  x2={28 * Math.cos((idx * 72 * Math.PI) / 180)}
                  y2={28 * Math.sin((idx * 72 * Math.PI) / 180)}
                  stroke="#F3F4F6"
                  strokeWidth="5"
                  strokeLinecap="round"
                />
              ))}
              <circle cx="0" cy="0" r="8" fill="#111827" stroke={neonColor} strokeWidth="2" />
            </g>
          </g>

          {/* Detailed Rear Wheel & Brembo Caliper */}
          <g transform="translate(680, 280)">
            <circle cx="0" cy="0" r="48" fill="#0A0C10" stroke="#1F2937" strokeWidth="8" />
            <circle cx="0" cy="0" r="34" fill="#374151" stroke="#9CA3AF" strokeWidth="2" />
            <path d="M -22 -20 C -15 -32, 5 -32, 15 -25 L 8 -12 C 0 -18, -10 -18, -15 -10 Z" fill="#EF4444" />
            <g transform={`rotate(${(frame * 25 * speedFactor) % 360})`}>
              <circle cx="0" cy="0" r="28" fill="none" stroke="url(#rimMetal)" strokeWidth="4" />
              {Array.from({ length: 5 }).map((_, idx) => (
                <line
                  key={idx}
                  x1="0"
                  y1="0"
                  x2={28 * Math.cos((idx * 72 * Math.PI) / 180)}
                  y2={28 * Math.sin((idx * 72 * Math.PI) / 180)}
                  stroke="#F3F4F6"
                  strokeWidth="5"
                  strokeLinecap="round"
                />
              ))}
              <circle cx="0" cy="0" r="8" fill="#111827" stroke={neonColor} strokeWidth="2" />
            </g>
          </g>
        </svg>

        {/* Dynamic Title / Badge Overlay */}
        <div
          style={{
            transform: `scale(${badgeSpring})`,
            marginTop: '-15px',
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
