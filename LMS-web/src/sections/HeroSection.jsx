import React, { useState } from 'react'
import {
  IconSparkles,
  IconArrowRight,
  IconMic,
  IconCheck,
  IconFlame,
  IconGlobe,
  IconUsers,
} from '../components/common/Icons.jsx'
import appIcon from '../assets/images/facetalk_app_icon.png'
import './HeroSection.css'

export default function HeroSection() {
  const [activeTab, setActiveTab] = useState('voiceroom')

  return (
    <section className="hero">
      {/* Soft background ambient glow */}
      <div className="hero__glow" />

      <div className="container hero__container">
        {/* Top Announcement Badge */}
        <div className="hero__badge">
          <IconSparkles size={14} />
          <span>The Next-Gen Language Exchange & Voice LMS</span>
        </div>

        {/* Headings - Reference Inspired */}
        <h1 className="hero__title">
          Connect Naturally, <br />
          <span className="hero__title-accent">Learn Languages with Confidence</span>
        </h1>

        <p className="hero__subtitle">
          Practice speaking with native speakers and passionate educators worldwide.
          Join drop-in voice rooms, get friendly in-line corrections, and build lasting conversational fluency.
        </p>

        {/* Action CTAs */}
        <div className="hero__actions">
          <a href="#download" className="btn btn-primary btn-lg">
            <span>Get FaceTalk Free</span>
            <IconArrowRight size={18} />
          </a>
          <a href="#features" className="btn btn-secondary btn-lg">
            <span>Explore App Features</span>
          </a>
        </div>

        {/* Central Realistic Mobile Device Showcase with Floating Badges */}
        <div className="hero__showcase">
          {/* Floating Widget 1: Top Left - Language Compatibility */}
          <div className="floating-card floating-card--top-left animate-float">
            <div className="floating-card__header">
              <span className="floating-card__label">Partner Match</span>
              <span className="floating-card__pill">98% Fit</span>
            </div>
            <div className="floating-card__content">
              <div className="floating-card__avatars">
                <span className="avatar avatar--blue">🇺🇸 Alex</span>
                <span className="avatar__swap">⇄</span>
                <span className="avatar avatar--purple">🇯🇵 Yuki</span>
              </div>
              <div className="floating-card__slider">
                <div className="floating-card__slider-bar" style={{ width: '88%' }} />
              </div>
            </div>
          </div>

          {/* Floating Widget 2: Top Right - Live Voice Room */}
          <div className="floating-card floating-card--top-right animate-float" style={{ animationDelay: '1.2s' }}>
            <div className="floating-card__header">
              <div className="floating-card__live-dot" />
              <span className="floating-card__label">Tokyo & London Voice Room</span>
            </div>
            <div className="floating-card__wave">
              <span className="wave-bar bar-1" />
              <span className="wave-bar bar-2" />
              <span className="wave-bar bar-3" />
              <span className="wave-bar bar-4" />
              <span className="wave-bar bar-5" />
            </div>
            <div className="floating-card__subtext">14 Speakers Active Now</div>
          </div>

          {/* Floating Widget 3: Bottom Left - Smart Correction */}
          <div className="floating-card floating-card--bottom-left animate-float" style={{ animationDelay: '2.5s' }}>
            <div className="floating-card__header">
              <IconCheck size={14} className="text-green" />
              <span className="floating-card__label">Instant In-Line Correction</span>
            </div>
            <div className="floating-card__correction">
              <span className="correction__del">I am agree with you</span>
              <span className="correction__arrow">➔</span>
              <span className="correction__add">I agree with you</span>
            </div>
            <div className="floating-card__subtext">Corrected by Elena (Native English)</div>
          </div>

          {/* Floating Widget 4: Bottom Right - Streak Counter */}
          <div className="floating-card floating-card--bottom-right animate-float" style={{ animationDelay: '1.8s' }}>
            <div className="floating-card__header">
              <IconFlame size={16} className="text-orange" />
              <span className="floating-card__label">24-Day Streak</span>
            </div>
            <div className="floating-card__streak-value">+420 Minutes Spoken</div>
            <div className="floating-card__streak-status">Fluency Score: 88/100</div>
          </div>

          {/* Central Mobile Phone Mockup */}
          <div className="phone-mockup">
            {/* Dynamic Island / Speaker */}
            <div className="phone-mockup__notch">
              <div className="phone-mockup__camera" />
              <div className="phone-mockup__sensor" />
            </div>

            {/* Screen Content */}
            <div className="phone-mockup__screen">
              {/* App Status Bar */}
              <div className="phone-mockup__statusbar">
                <span>9:41</span>
                <div className="phone-mockup__status-icons">
                  <span>5G</span>
                  <span>100%</span>
                </div>
              </div>

              {/* App Header */}
              <div className="phone-mockup__app-header">
                <div className="phone-mockup__app-brand">
                  <img src={appIcon} alt="App" className="phone-mockup__logo-badge" />
                  <div>
                    <div className="phone-mockup__app-title">FaceTalk</div>
                    <div className="phone-mockup__app-subtitle">Live Language Room</div>
                  </div>
                </div>
                <div className="phone-mockup__header-action">
                  <IconMic size={16} />
                </div>
              </div>

              {/* In-App Tab Switcher */}
              <div className="phone-mockup__tabs">
                <button
                  type="button"
                  className={`phone-tab ${activeTab === 'voiceroom' ? 'phone-tab--active' : ''}`}
                  onClick={() => setActiveTab('voiceroom')}
                >
                  Voice Room
                </button>
                <button
                  type="button"
                  className={`phone-tab ${activeTab === 'chat' ? 'phone-tab--active' : ''}`}
                  onClick={() => setActiveTab('chat')}
                >
                  Chat & Fix
                </button>
              </div>

              {/* App Screen Body */}
              {activeTab === 'voiceroom' ? (
                <div className="phone-mockup__room-view">
                  <div className="room-topic-badge">
                    <span>☕ Daily English Conversation & Slang</span>
                  </div>

                  {/* Speaker Grid */}
                  <div className="speaker-grid">
                    <div className="speaker-tile speaker-tile--active">
                      <div className="speaker-avatar">🇺🇸</div>
                      <div className="speaker-name">Sarah (Host)</div>
                      <div className="speaker-tag">Speaking...</div>
                    </div>
                    <div className="speaker-tile">
                      <div className="speaker-avatar">🇫🇷</div>
                      <div className="speaker-name">Lucas</div>
                      <div className="speaker-tag">Learner</div>
                    </div>
                    <div className="speaker-tile">
                      <div className="speaker-avatar">🇰🇷</div>
                      <div className="speaker-name">Minho</div>
                      <div className="speaker-tag">Learner</div>
                    </div>
                    <div className="speaker-tile">
                      <div className="speaker-avatar">🇪🇸</div>
                      <div className="speaker-name">Sofia</div>
                      <div className="speaker-tag">Learner</div>
                    </div>
                  </div>

                  {/* Wave visualizer */}
                  <div className="live-audio-bar">
                    <div className="live-audio-info">
                      <span className="live-pulse" />
                      <span>Live Audio Stream</span>
                    </div>
                    <div className="live-audio-waves">
                      <span style={{ height: '14px' }} />
                      <span style={{ height: '26px' }} />
                      <span style={{ height: '18px' }} />
                      <span style={{ height: '32px' }} />
                      <span style={{ height: '22px' }} />
                      <span style={{ height: '12px' }} />
                    </div>
                  </div>
                </div>
              ) : (
                <div className="phone-mockup__chat-view">
                  <div className="chat-bubble chat-bubble--incoming">
                    <div className="chat-bubble__sender">Kenji (Tokyo)</div>
                    <div className="chat-bubble__text">
                      Hello! I want to practice my English interview tomorrow.
                    </div>
                  </div>

                  <div className="chat-bubble chat-bubble--outgoing">
                    <div className="chat-bubble__text">
                      Awesome! I am happy to helping you prep.
                    </div>
                  </div>

                  {/* Correction widget inside chat */}
                  <div className="chat-correction-card">
                    <div className="correction-badge">
                      <IconCheck size={12} /> Partner Correction
                    </div>
                    <div className="correction-text">
                      <span className="correction-strike">helping</span> ➔ <span className="correction-good">help</span> you prep.
                    </div>
                    <div className="correction-explain">Tip: Use bare infinitive after "happy to".</div>
                  </div>
                </div>
              )}

              {/* Bottom Nav inside Phone */}
              <div className="phone-mockup__nav">
                <span className="phone-nav-item phone-nav-item--active">💬 Messages</span>
                <span className="phone-nav-item">👥 Connect</span>
                <span className="phone-nav-item">🎙️ Voice</span>
                <span className="phone-nav-item">👤 Me</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
