import React, { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { IconShield, IconLock } from '../components/common/Icons.jsx'
import './LegalPage.css'

export default function Privacy() {
  useEffect(() => {
    window.scrollTo(0, 0)
  }, [])

  return (
    <div className="legal-page">
      <div className="container">
        {/* Header */}
        <div className="legal-header">
          <Link to="/" className="legal-header__back">
            ← Back to FaceTalk Overview
          </Link>
          <div className="legal-header__tag">
            <IconShield size={14} />
            <span>Data Protection</span>
          </div>
          <h1 className="legal-header__title">Privacy Policy</h1>
          <div className="legal-header__meta">
            <span>Last Updated: September 2026</span>
            <span>•</span>
            <span>Compliance with GDPR, CCPA, and App Store Guidelines</span>
          </div>
        </div>

        {/* Content Layout */}
        <div className="legal-layout">
          {/* Sidebar */}
          <aside className="legal-sidebar">
            <div className="legal-sidebar__title">Table of Contents</div>
            <nav className="legal-sidebar__nav">
              <a href="#overview" className="legal-sidebar__link">1. Privacy Overview</a>
              <a href="#collection" className="legal-sidebar__link">2. Information We Collect</a>
              <a href="#microphone" className="legal-sidebar__link">3. Microphone & Audio Usage</a>
              <a href="#usage" className="legal-sidebar__link">4. How We Use Information</a>
              <a href="#sharing" className="legal-sidebar__link">5. Data Sharing & Infrastructure</a>
              <a href="#security" className="legal-sidebar__link">6. Security & Encryption</a>
              <a href="#retention" className="legal-sidebar__link">7. Data Retention & Deletion</a>
              <a href="#rights" className="legal-sidebar__link">8. Your Privacy Rights (GDPR/CCPA)</a>
              <a href="#children" className="legal-sidebar__link">9. Children's Privacy</a>
              <a href="#contact" className="legal-sidebar__link">10. Contact Data Protection Officer</a>
            </nav>

            <div className="legal-sidebar__other-policies">
              <span className="legal-sidebar__other-title">Other Policies</span>
              <Link to="/terms" className="legal-sidebar__other-link">Terms & Conditions ➔</Link>
              <Link to="/refund" className="legal-sidebar__other-link">Refund Policy ➔</Link>
            </div>
          </aside>

          {/* Main Content */}
          <main className="legal-content">
            <div className="legal-box-highlight">
              <p>
                <strong>Your privacy is paramount.</strong> FaceTalk is dedicated to facilitating safe, respectful language exchange without compromising your personal identity, private messages, or audio streams.
              </p>
            </div>

            <section id="overview">
              <h2>1. Privacy Overview</h2>
              <p>
                This Privacy Policy describes how FaceTalk collects, utilizes, and protects your personal data when you use the FaceTalk mobile application and associated web interfaces. We never sell your personal information or conversations to data brokers or third-party advertisers.
              </p>
            </section>

            <section id="collection">
              <h2>2. Information We Collect</h2>
              <p>To provide accurate partner matching and educational features, we collect:</p>
              <ul>
                <li>
                  <strong>Account & Profile Information:</strong> Display name, profile avatar, native language, target language(s), self-assessed fluency levels, bio, and country/region.
                </li>
                <li>
                  <strong>Communication Data:</strong> In-app 1-on-1 messages, in-line corrections, and public Moments posts you intentionally submit.
                </li>
                <li>
                  <strong>Device & Diagnostic Data:</strong> IP address, operating system version (iOS/Android), device identifiers, language settings, and crash logs to maintain app stability.
                </li>
              </ul>
            </section>

            <section id="microphone">
              <h2>3. Microphone & Audio Permissions</h2>
              <p>
                FaceTalk requires access to your device’s microphone strictly to enable Voice Rooms and 1-on-1 voice messaging.
              </p>
              <div className="legal-box-highlight">
                <p>
                  <strong>No Secret Audio Recording:</strong> Real-time audio in Voice Rooms is streamed transiently and is <em>not recorded or archived</em> on our servers unless an in-app report is triggered for immediate safety investigation.
                </p>
              </div>
            </section>

            <section id="usage">
              <h2>4. How We Use Your Information</h2>
              <p>We process your data strictly to:</p>
              <ul>
                <li>Match you with compatible native language partners and students.</li>
                <li>Facilitate real-time text chats, voice messaging, and Voice Room discussions.</li>
                <li>Display user-submitted grammar and spelling corrections.</li>
                <li>Detect, prevent, and mitigate spam, fraud, offensive content, and safety violations.</li>
                <li>Provide customer care and respond to inquiries.</li>
              </ul>
            </section>

            <section id="sharing">
              <h2>5. Data Sharing & Infrastructure</h2>
              <p>
                We do not sell your personal data. We utilize reputable cloud service providers to maintain reliability:
              </p>
              <ul>
                <li><strong>Firebase & Google Cloud:</strong> Secure database storage, authentication, push notifications, and real-time synchronization.</li>
                <li><strong>Payment Processors:</strong> Official Apple App Store and Google Play Store billing channels for handling VIP memberships securely.</li>
              </ul>
            </section>

            <section id="security">
              <h2>6. Security & Encryption</h2>
              <p>
                We employ industry-standard encryption protocols (TLS/SSL in transit, AES-256 at rest) to safeguard your profile and messaging information. Access to backend databases is strictly restricted to authorized engineers on a least-privilege basis.
              </p>
            </section>

            <section id="retention">
              <h2>7. Data Retention & Deletion</h2>
              <p>
                We retain your information as long as your account is active. You may request account deletion at any time directly through the mobile app settings (<code>Profile ➔ Settings ➔ Delete Account</code>) or by contacting support. Upon account deletion, all personal data, chats, and moments are permanently purged within 30 days.
              </p>
            </section>

            <section id="rights">
              <h2>8. Your Privacy Rights (GDPR & CCPA)</h2>
              <p>Regardless of your geographic location, you enjoy the right to:</p>
              <ul>
                <li>Access and obtain an export copy of your personal data.</li>
                <li>Correct any inaccurate or incomplete profile information.</li>
                <li>Object to or restrict specific data processing.</li>
                <li>Delete your account and request erasure of your data ("Right to be Forgotten").</li>
              </ul>
            </section>

            <section id="children">
              <h2>9. Children’s Privacy</h2>
              <p>
                FaceTalk is not intended for children under 13 years of age. We do not knowingly collect personal data from minors below this threshold. If we discover an account registered by an ineligible minor, it will be deleted promptly.
              </p>
            </section>

            <section id="contact">
              <h2>10. Contact Data Protection Officer</h2>
              <p>For inquiries regarding privacy, data rights, or GDPR inquiries, reach out to:</p>
              <div className="legal-box-highlight">
                <p><strong>FaceTalk Data Protection Officer (DPO)</strong></p>
                <p>Email: <code>privacy@facetalk-app.com</code></p>
                <p>Phone: <code>+94 77 555 9738</code></p>
                <p>Address: 37/9, Mahakatuwana Rd, Homagama, Western Province, Sri Lanka</p>
                <p>Response Time: Typically within 48 business hours</p>
              </div>
            </section>
          </main>
        </div>
      </div>
    </div>
  )
}
