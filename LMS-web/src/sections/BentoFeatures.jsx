import React, { useState } from 'react'
import {
  IconMic,
  IconChat,
  IconUsers,
  IconGlobe,
  IconFlame,
  IconCheck,
  IconSparkles,
} from '../components/common/Icons.jsx'
import './BentoFeatures.css'

export default function BentoFeatures() {
  const [activeVoiceRoom, setActiveVoiceRoom] = useState(0)
  const [correctionAccepted, setCorrectionAccepted] = useState(false)

  const voiceRoomTopics = [
    { title: 'Tokyo ➔ London English Café', level: 'Intermediate', count: 18, flag: '🇬🇧 🇯🇵' },
    { title: 'Spanish for Daily Travel', level: 'Beginner Friendly', count: 24, flag: '🇪🇸 🇲🇽' },
    { title: 'JLPT N2 Grammar Clinic', level: 'Advanced', count: 12, flag: '🇯🇵 🌏' },
  ]

  return (
    <section id="features" className="section bento-section">
      <div className="container">
        {/* Header */}
        <div className="section-header">
          <div className="section-tag">
            <IconSparkles size={14} />
            <span>Interactive Features</span>
          </div>
          <h2 className="section-title">Master Any Language with Human Connection</h2>
          <p className="section-subtitle">
            FaceTalk brings real immersion to your pocket with live audio spaces, collaborative corrections, and personalized learning streaks.
          </p>
        </div>

        {/* Bento Grid */}
        <div className="bento-grid">
          {/* Card 1: Drop-in Voice Rooms */}
          <div className="bento-card bento-card--voiceroom">
            <div className="bento-card__badge">
              <IconMic size={14} />
              <span>Live Audio</span>
            </div>
            <h3 className="bento-card__heading">Drop-in Voice Rooms</h3>
            <p className="bento-card__desc">
              Listen in or take the mic. Practice speaking freely in relaxed group discussions hosted by native speakers and educators.
            </p>

            {/* Interactive Voice Room Simulator */}
            <div className="bento-voiceroom-preview">
              <div className="voiceroom-chips">
                {voiceRoomTopics.map((topic, i) => (
                  <button
                    key={i}
                    type="button"
                    className={`topic-chip ${activeVoiceRoom === i ? 'topic-chip--active' : ''}`}
                    onClick={() => setActiveVoiceRoom(i)}
                  >
                    <span>{topic.flag}</span>
                    <span>{topic.title}</span>
                  </button>
                ))}
              </div>

              <div className="voiceroom-active-stage">
                <div className="stage-info">
                  <span className="stage-pulse" />
                  <span className="stage-title">{voiceRoomTopics[activeVoiceRoom].title}</span>
                  <span className="stage-badge">{voiceRoomTopics[activeVoiceRoom].level}</span>
                </div>
                <div className="stage-speakers">
                  <div className="stage-avatar stage-avatar--talking">
                    <span>👩‍🏫</span>
                    <span className="talking-ring" />
                  </div>
                  <div className="stage-avatar"><span>👨‍🎓</span></div>
                  <div className="stage-avatar"><span>👩‍🎨</span></div>
                  <div className="stage-avatar"><span>🧑‍💻</span></div>
                  <span className="stage-extra">+{voiceRoomTopics[activeVoiceRoom].count}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Card 2: In-Line Corrections */}
          <div className="bento-card bento-card--corrections">
            <div className="bento-card__badge">
              <IconCheck size={14} />
              <span>Smart Corrections</span>
            </div>
            <h3 className="bento-card__heading">In-Line Corrections</h3>
            <p className="bento-card__desc">
              Get gentle feedback right inside the chat bubble. Native partners correct sentences with side-by-side explanations.
            </p>

            {/* Interactive Correction Widget */}
            <div className="bento-correction-box">
              <div className="correction-box__header">
                <span className="correction-box__user">🇯🇵 Kenji:</span>
                <span className="correction-box__status">
                  {correctionAccepted ? '✓ Applied' : 'Tap to accept'}
                </span>
              </div>
              <div className="correction-box__body">
                {correctionAccepted ? (
                  <div className="correction-sentence sentence--accepted">
                    "I look forward to <span className="highlight-green">meeting</span> you tomorrow!"
                  </div>
                ) : (
                  <div className="correction-sentence">
                    "I look forward to <span className="highlight-red">meet</span> you tomorrow!"
                  </div>
                )}
              </div>
              <div className="correction-box__action">
                <button
                  type="button"
                  className="btn btn-secondary btn-sm"
                  onClick={() => setCorrectionAccepted(!correctionAccepted)}
                >
                  <IconCheck size={14} />
                  <span>{correctionAccepted ? 'Reset Sentence' : 'Accept Native Correction'}</span>
                </button>
              </div>
            </div>
          </div>

          {/* Card 3: 1-on-1 Native Exchange */}
          <div className="bento-card bento-card--exchange">
            <div className="bento-card__badge">
              <IconUsers size={14} />
              <span>50/50 Balance</span>
            </div>
            <h3 className="bento-card__heading">Smart Partner Match</h3>
            <p className="bento-card__desc">
              Automatic pairing matching your target language with partners fluent in it, ensuring fair speaking time for both sides.
            </p>

            <div className="exchange-gauge-box">
              <div className="exchange-pair">
                <div className="exchange-lang">
                  <span className="exchange-flag">🇺🇸</span>
                  <span className="exchange-name">English</span>
                  <span className="exchange-time">15 min</span>
                </div>
                <div className="exchange-divider">⇄</div>
                <div className="exchange-lang">
                  <span className="exchange-flag">🇪🇸</span>
                  <span className="exchange-name">Spanish</span>
                  <span className="exchange-time">15 min</span>
                </div>
              </div>
              <div className="exchange-progress-bar">
                <div className="progress-half progress-half--left" />
                <div className="progress-half progress-half--right" />
              </div>
              <div className="exchange-balanced-note">
                <IconCheck size={13} className="text-green" /> Perfect 1:1 Exchange Time
              </div>
            </div>
          </div>

          {/* Card 4: Global Moments Feed (Wide) */}
          <div className="bento-card bento-card--moments bento-card--wide">
            <div className="bento-card__badge">
              <IconGlobe size={14} />
              <span>Community Feed</span>
            </div>
            <h3 className="bento-card__heading">Global Moments & Cultural Exchange</h3>
            <p className="bento-card__desc">
              Post audio snippets, photography, and cultural queries. Native speakers in that country leave voice comments, translations, and warm cheer.
            </p>

            <div className="moments-mini-feed">
              <div className="moment-post">
                <div className="moment-author">
                  <span className="moment-avatar">🇩🇪</span>
                  <div>
                    <div className="moment-name">Hannah (Berlin)</div>
                    <div className="moment-sub">Learning Japanese • 2h ago</div>
                  </div>
                </div>
                <p className="moment-text">
                  "Today I tried ordering matcha in Kyoto using Japanese only! How does my accent sound?"
                </p>
                <div className="moment-audio-pill">
                  <span className="audio-play-icon">▶</span>
                  <span className="audio-wave-bars">||||||||||||||||||||</span>
                  <span className="audio-duration">0:14</span>
                </div>
              </div>
            </div>
          </div>

          {/* Card 5: Growth Score & Streaks (Directly inspired by Reference Image's Growth Score arc) */}
          <div className="bento-card bento-card--growth bento-card--wide">
            <div className="bento-card__badge">
              <IconFlame size={14} />
              <span>Fluency Score</span>
            </div>
            <h3 className="bento-card__heading">Fluency Growth & Daily Streaks</h3>
            <p className="bento-card__desc">
              Track retention, vocabulary milestones, and daily voice time. Consistent daily practice boosts your fluency score and unlock badges.
            </p>

            {/* Arc / Semi-circle Gauge matching reference image */}
            <div className="growth-score-box">
              <div className="growth-gauge">
                <svg viewBox="0 0 160 90" className="gauge-svg">
                  <path
                    d="M 15 80 A 65 65 0 0 1 145 80"
                    fill="none"
                    stroke="#EAEAF4"
                    strokeWidth="14"
                    strokeLinecap="round"
                  />
                  <path
                    d="M 15 80 A 65 65 0 0 1 145 80"
                    fill="none"
                    stroke="url(#purpleGradient)"
                    strokeWidth="14"
                    strokeLinecap="round"
                    strokeDasharray="204"
                    strokeDashoffset="38"
                  />
                  <defs>
                    <linearGradient id="purpleGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                      <stop offset="0%" stopColor="#7B68F4" />
                      <stop offset="100%" stopColor="#4FA8FF" />
                    </linearGradient>
                  </defs>
                </svg>
                <div className="gauge-score">
                  <span className="gauge-number">84</span>
                  <span className="gauge-label">Fluency Index</span>
                </div>
              </div>

              <div className="growth-details">
                <div className="growth-stat">
                  <span className="stat-label">Daily Streak</span>
                  <span className="stat-val text-orange">🔥 24 Days</span>
                </div>
                <div className="growth-stat">
                  <span className="stat-label">Words Mastered</span>
                  <span className="stat-val">840 Words</span>
                </div>
                <div className="growth-stat">
                  <span className="stat-label">Speaking Time</span>
                  <span className="stat-val">38.5 Hours</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
