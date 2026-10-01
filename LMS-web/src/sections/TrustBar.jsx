import React from 'react'
import { platformStats } from '../data/contentData.js'
import './TrustBar.css'

export default function TrustBar() {
  const popularLanguages = [
    { name: 'English', code: 'EN', flag: '🇺🇸' },
    { name: 'Spanish', code: 'ES', flag: '🇪🇸' },
    { name: 'Japanese', code: 'JA', flag: '🇯🇵' },
    { name: 'French', code: 'FR', flag: '🇫🇷' },
    { name: 'German', code: 'DE', flag: '🇩🇪' },
    { name: 'Korean', code: 'KO', flag: '🇰🇷' },
    { name: 'Mandarin', code: 'ZH', flag: '🇨🇳' },
    { name: 'Italian', code: 'IT', flag: '🇮🇹' },
  ]

  return (
    <section className="trustbar">
      <div className="container">
        {/* Subtle trust title */}
        <p className="trustbar__label">
          Trusted by language learners, educators, and polyglots across the globe
        </p>

        {/* Stats Grid */}
        <div className="trustbar__stats">
          {platformStats.map((stat, idx) => (
            <div key={idx} className="trustbar__stat-item">
              <div className="trustbar__stat-value">{stat.value}</div>
              <div className="trustbar__stat-label">{stat.label}</div>
            </div>
          ))}
        </div>

        {/* Floating Language Exchange Tags */}
        <div className="trustbar__chips">
          {popularLanguages.map((lang, index) => (
            <div key={index} className="trustbar__chip">
              <span className="trustbar__chip-flag">{lang.flag}</span>
              <span className="trustbar__chip-name">{lang.name}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
