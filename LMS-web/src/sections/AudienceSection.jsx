import React, { useState } from 'react'
import { audiencePersonas } from '../data/contentData.js'
import {
  IconGraduationCap,
  IconUsers,
  IconAward,
  IconCompass,
  IconCheck,
  IconArrowRight,
} from '../components/common/Icons.jsx'
import './AudienceSection.css'

export default function AudienceSection() {
  const [selectedPersonaId, setSelectedPersonaId] = useState(audiencePersonas[0].id)

  const getPersonaIcon = (id) => {
    switch (id) {
      case 'students':
        return <IconGraduationCap size={24} />
      case 'native-speakers':
        return <IconUsers size={24} />
      case 'tutors':
        return <IconAward size={24} />
      case 'expats':
        return <IconCompass size={24} />
      default:
        return <IconUsers size={24} />
    }
  }

  const activePersona = audiencePersonas.find((p) => p.id === selectedPersonaId) || audiencePersonas[0]

  return (
    <section id="audience" className="section section--alt audience">
      <div className="container">
        {/* Section Header */}
        <div className="section-header">
          <div className="section-tag">
            <IconUsers size={14} />
            <span>Target Community</span>
          </div>
          <h2 className="section-title">Who is FaceTalk Built For?</h2>
          <p className="section-subtitle">
            Whether you are preparing for a critical exam, relocating across the globe, or sharing your native tongue, FaceTalk adapts to your exact goals.
          </p>
        </div>

        {/* Persona Selector Tabs */}
        <div className="audience__tabs">
          {audiencePersonas.map((persona) => {
            const isActive = persona.id === selectedPersonaId
            return (
              <button
                key={persona.id}
                type="button"
                className={`audience__tab-btn ${isActive ? 'audience__tab-btn--active' : ''}`}
                onClick={() => setSelectedPersonaId(persona.id)}
              >
                <span className="audience__tab-icon">{getPersonaIcon(persona.id)}</span>
                <span className="audience__tab-title">{persona.title}</span>
              </button>
            )
          })}
        </div>

        {/* Active Persona Featured Card */}
        <div className="audience__card bento-card">
          <div className="audience__card-grid">
            <div className="audience__card-main">
              <div className="audience__card-badge" style={{ color: activePersona.tagColor, borderColor: activePersona.tagColor }}>
                {activePersona.badge}
              </div>
              <h3 className="audience__card-title">{activePersona.title}</h3>
              <p className="audience__card-desc">{activePersona.description}</p>

              <div className="audience__points">
                {activePersona.points.map((pt, i) => (
                  <div key={i} className="audience__point-item">
                    <div className="audience__check-icon">
                      <IconCheck size={14} />
                    </div>
                    <span>{pt}</span>
                  </div>
                ))}
              </div>

              <div className="audience__card-footer">
                <a href="#download" className="btn btn-primary btn-sm">
                  <span>Start as a {activePersona.title.split('&')[0].trim()}</span>
                  <IconArrowRight size={15} />
                </a>
              </div>
            </div>

            {/* Right side visual callout */}
            <div className="audience__card-stat-box">
              <div className="stat-box__highlight-quote">
                "{activePersona.highlight}"
              </div>
              <div className="stat-box__perk-list">
                <div className="perk-item">
                  <span className="perk-bullet" />
                  <span>Real-time human feedback</span>
                </div>
                <div className="perk-item">
                  <span className="perk-bullet" />
                  <span>Safe & verified profiles</span>
                </div>
                <div className="perk-item">
                  <span className="perk-bullet" />
                  <span>Customizable fluency goals</span>
                </div>
              </div>
              <div className="stat-box__tagline">
                Join 500,000+ members speaking daily on FaceTalk
              </div>
            </div>
          </div>
        </div>

        {/* 4 Quick Demographic Cards Grid */}
        <div className="audience__demographics-grid">
          {audiencePersonas.map((persona) => (
            <div
              key={persona.id}
              className={`demographic-card ${persona.id === selectedPersonaId ? 'demographic-card--active' : ''}`}
              onClick={() => setSelectedPersonaId(persona.id)}
            >
              <div className="demographic-card__icon" style={{ color: persona.tagColor }}>
                {getPersonaIcon(persona.id)}
              </div>
              <h4 className="demographic-card__title">{persona.title}</h4>
              <p className="demographic-card__summary">{persona.description.slice(0, 85)}...</p>
              <span className="demographic-card__link">
                View Demographics ➔
              </span>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
