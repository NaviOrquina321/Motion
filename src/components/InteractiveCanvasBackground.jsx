import React, { useEffect, useRef } from 'react';

export const InteractiveCanvasBackground = ({ mousePos, neonColor, driveMode }) => {
  const canvasRef = useRef(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let animationFrameId;

    let width = (canvas.width = window.innerWidth);
    let height = (canvas.height = window.innerHeight);

    const handleResize = () => {
      width = canvas.width = window.innerWidth;
      height = canvas.height = window.innerHeight;
    };
    window.addEventListener('resize', handleResize);

    // Particle system
    const particleCount = driveMode === 'Track' ? 120 : driveMode === 'Sport' ? 80 : 50;
    const particles = Array.from({ length: particleCount }).map(() => ({
      x: Math.random() * width,
      y: Math.random() * height,
      size: Math.random() * 2.5 + 0.5,
      speedX: (Math.random() - 0.5) * (driveMode === 'Track' ? 3 : 1.5),
      speedY: (Math.random() - 0.5) * (driveMode === 'Track' ? 3 : 1.5),
      opacity: Math.random() * 0.7 + 0.3,
    }));

    let frame = 0;

    const render = () => {
      frame++;
      ctx.clearRect(0, 0, width, height);

      // Mouse influence tilt glow
      const gradX = mousePos.x || width / 2;
      const gradY = mousePos.y || height / 2;

      const radialGrad = ctx.createRadialGradient(
        gradX,
        gradY,
        50,
        gradX,
        gradY,
        Math.max(width, height) * 0.6
      );
      radialGrad.addColorStop(0, `${neonColor}18`);
      radialGrad.addColorStop(0.5, 'rgba(10, 11, 14, 0.4)');
      radialGrad.addColorStop(1, '#0A0B0E');

      ctx.fillStyle = radialGrad;
      ctx.fillRect(0, 0, width, height);

      // Draw interactive grid lines
      ctx.strokeStyle = `${neonColor}12`;
      ctx.lineWidth = 1;
      const gridSize = 80;
      const offsetX = (mousePos.x - width / 2) * 0.05;
      const offsetY = (mousePos.y - height / 2) * 0.05;

      for (let x = 0; x < width; x += gridSize) {
        ctx.beginPath();
        ctx.moveTo(x + offsetX, 0);
        ctx.lineTo(x + offsetX, height);
        ctx.stroke();
      }
      for (let y = 0; y < height; y += gridSize) {
        ctx.beginPath();
        ctx.moveTo(0, y + offsetY);
        ctx.lineTo(width, y + offsetY);
        ctx.stroke();
      }

      // Draw animated particles reacting to mouse position
      particles.forEach((p) => {
        p.x += p.speedX;
        p.y += p.speedY;

        if (p.x < 0) p.x = width;
        if (p.x > width) p.x = 0;
        if (p.y < 0) p.y = height;
        if (p.y > height) p.y = 0;

        // Mouse attraction / repulse
        const dx = mousePos.x - p.x;
        const dy = mousePos.y - p.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 180) {
          p.x -= (dx / dist) * 1.5;
          p.y -= (dy / dist) * 1.5;
        }

        ctx.fillStyle = neonColor;
        ctx.globalAlpha = p.opacity;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fill();
      });

      ctx.globalAlpha = 1.0;
      animationFrameId = requestAnimationFrame(render);
    };

    render();

    return () => {
      window.removeEventListener('resize', handleResize);
      cancelAnimationFrame(animationFrameId);
    };
  }, [mousePos, neonColor, driveMode]);

  return (
    <canvas
      ref={canvasRef}
      className="fixed inset-0 pointer-events-none z-0 transition-opacity duration-500"
    />
  );
};
