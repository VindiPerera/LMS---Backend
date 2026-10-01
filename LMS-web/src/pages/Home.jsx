import React from 'react'
import HeroSection from '../sections/HeroSection.jsx'
import TrustBar from '../sections/TrustBar.jsx'
import AudienceSection from '../sections/AudienceSection.jsx'
import BentoFeatures from '../sections/BentoFeatures.jsx'
import HowItWorksSection from '../sections/HowItWorksSection.jsx'
import FaqSection from '../sections/FaqSection.jsx'
import CtaSection from '../sections/CtaSection.jsx'

export default function Home() {
  return (
    <div className="home-page">
      <HeroSection />
      <TrustBar />
      <AudienceSection />
      <BentoFeatures />
      <HowItWorksSection />
      <FaqSection />
      <CtaSection />
    </div>
  )
}
