import React from 'react';
import { interpolate, useCurrentFrame, useVideoConfig } from 'remotion';

export const SupraGaugeComposition = ({
  hp = 382,
  torque = 500,
  accentColor = '#FF0033',
}) => {
  const frame = useCurrentFrame();
  const { durationInFrames } = useVideoConfig();

  // Interpolate gauge value
  const progress = interpolate(frame, [0, durationInFrames * 0.7], [0, 1], {
    extrapolateRight: 'clamp',
  });

  const currentHp = Math.round(progress * hp);
  const currentTorque = Math.round(progress * torque);

  const strokeDashoffset = 440 - (440 * (currentHp / 500));

  return (
    <div
      style={{
        width: '100%',
        height: '100%',
        backgroundColor: '#0A0B0E',
        fontFamily: 'Orbitron, sans-serif',
      }}
      className="flex items-center justify-center p-6 text-white"
    >
      <div className="grid grid-cols-2 gap-8 w-full max-w-xl bg-card border border-white/10 rounded-2xl p-6 shadow-2xl backdrop-blur-md">
        {/* Horsepower Circular Gauge */}
        <div className="flex flex-col items-center justify-center relative">
          <svg className="w-40 h-40 transform -rotate-90">
            <circle
              cx="80"
              cy="80"
              r="70"
              stroke="#1F2937"
              strokeWidth="12"
              fill="transparent"
            />
            <circle
              cx="80"
              cy="80"
              r="70"
              stroke={accentColor}
              strokeWidth="12"
              fill="transparent"
              strokeDasharray="440"
              strokeDashoffset={strokeDashoffset}
              strokeLinecap="round"
              className="transition-all duration-100"
            />
          </svg>
          <div className="absolute flex flex-col items-center">
            <span className="text-3xl font-black">{currentHp}</span>
            <span className="text-xs text-gray-400 font-mono">HORSEPOWER</span>
          </div>
        </div>

        {/* Torque & Acceleration Specs */}
        <div className="flex flex-col justify-center gap-4 font-mono">
          <div>
            <div className="text-xs text-gray-400 mb-1">TORQUE (NM)</div>
            <div className="flex items-center gap-2">
              <span className="text-2xl font-bold text-cyan-400">{currentTorque}</span>
              <span className="text-xs text-gray-500">@ 1800-5000 RPM</span>
            </div>
            <div className="w-full bg-gray-800 h-2 rounded-full mt-1 overflow-hidden">
              <div
                style={{
                  width: `${(currentTorque / 600) * 100}%`,
                  backgroundColor: '#00F0FF',
                }}
                className="h-full rounded-full"
              />
            </div>
          </div>

          <div>
            <div className="text-xs text-gray-400 mb-1">0-100 KM/H (0-60 MPH)</div>
            <div className="text-3xl font-extrabold text-yellow-400">
              3.9 <span className="text-sm font-normal text-gray-400">SECONDS</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
