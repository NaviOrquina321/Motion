import { Composition } from 'remotion';
import { SupraHeroComposition } from './SupraHeroComposition';
import { SupraGaugeComposition } from './SupraGaugeComposition';

export const RemotionRoot = () => {
  return (
    <>
      <Composition
        id="SupraHero"
        component={SupraHeroComposition}
        durationInFrames={180}
        fps={30}
        width={1280}
        height={720}
        defaultProps={{
          carColor: '#FF0033',
          neonColor: '#00F0FF',
          driveMode: 'Sport',
          isRevving: false,
          showFlame: true,
          hudTitle: 'GR SUPRA MK5',
        }}
      />
      <Composition
        id="SupraGauge"
        component={SupraGaugeComposition}
        durationInFrames={120}
        fps={30}
        width={800}
        height={450}
        defaultProps={{
          hp: 382,
          torque: 500,
          accentColor: '#FF0033',
        }}
      />
    </>
  );
};
