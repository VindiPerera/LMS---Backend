import React from 'react'
import { howItWorksSteps } from '../data/contentData.js'
import { IconSparkles } from '../components/common/Icons.jsx'
import './HowItWorksSection.css'

export default function HowItWorksSection() {
  return (
    <section id="how-it-works" className="section section--alt how-it-works">
      <div className="container">
        <div className="section-header">
          <div className="section-tag">
            <IconSparkles size={14} />
            <span>Effortless Learning</span>
          </div>
          <h2 className="section-title">How FaceTalk Works in 3 Steps</h2>
          <p className="section-subtitle">
            From your very first tap to holding natural conversations with native speakers across time zones.
          </p>
        </div>

        <div className="how-it-works__grid">
          {howItWorksSteps.map((stepItem, idx) => (
            <div key={idx} className="step-card">
              <div className="step-card__number">{stepItem.step}</div>
              <h3 className="step-card__title">{stepItem.title}</h3>
              <p className="step-card__desc">{stepItem.description}</p>
              <div className="step-card__line" />
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
