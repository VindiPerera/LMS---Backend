import React, { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { IconFileText, IconArrowRight, IconShield } from '../components/common/Icons.jsx'
import './LegalPage.css'

export default function Terms() {
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
            <IconFileText size={14} />
            <span>Legal Agreement</span>
          </div>
          <h1 className="legal-header__title">Terms & Conditions</h1>
          <div className="legal-header__meta">
            <span>Last Updated: September 2026</span>
            <span>•</span>
            <span>Applies to FaceTalk Mobile & Web Services</span>
          </div>
        </div>

        {/* Content Layout */}
        <div className="legal-layout">
          {/* Table of contents sidebar */}
          <aside className="legal-sidebar">
            <div className="legal-sidebar__title">Table of Contents</div>
            <nav className="legal-sidebar__nav">
              <a href="#acceptance" className="legal-sidebar__link">1. Acceptance of Terms</a>
              <a href="#eligibility" className="legal-sidebar__link">2. Eligibility & Accounts</a>
              <a href="#purpose" className="legal-sidebar__link">3. Platform Purpose & Use</a>
              <a href="#code-of-conduct" className="legal-sidebar__link">4. Community Guidelines</a>
              <a href="#voice-rooms" className="legal-sidebar__link">5. Voice Rooms & Audio</a>
              <a href="#subscriptions" className="legal-sidebar__link">6. In-App VIP Subscriptions</a>
              <a href="#intellectual-prop" className="legal-sidebar__link">7. Intellectual Property</a>
              <a href="#termination" className="legal-sidebar__link">8. Termination</a>
              <a href="#disclaimer" className="legal-sidebar__link">9. Disclaimers & Liability</a>
              <a href="#contact" className="legal-sidebar__link">10. Contact Us</a>
            </nav>

            <div className="legal-sidebar__other-policies">
              <span className="legal-sidebar__other-title">Other Policies</span>
              <Link to="/privacy" className="legal-sidebar__other-link">Privacy Policy ➔</Link>
              <Link to="/refund" className="legal-sidebar__other-link">Refund Policy ➔</Link>
            </div>
          </aside>

          {/* Main Document Body */}
          <main className="legal-content">
            <div className="legal-box-highlight">
              <p>
                <strong>Welcome to FaceTalk.</strong> By downloading, accessing, or using the FaceTalk mobile application and related web services, you agree to be bound by these Terms and Conditions. Please read them thoroughly.
              </p>
            </div>

            <section id="acceptance">
              <h2>1. Acceptance of Terms</h2>
              <p>
                These Terms and Conditions ("Terms") constitute a legally binding agreement between you ("User", "you") and the operators of FaceTalk ("FaceTalk", "we", "our", or "us"). If you do not agree with any part of these Terms, you must immediately discontinue use of the FaceTalk application.
              </p>
            </section>

            <section id="eligibility">
              <h2>2. Eligibility & Accounts</h2>
              <p>
                To register an account on FaceTalk, you must be at least 13 years old (or 16 years old in certain jurisdictions such as the European Union). By creating an account, you represent and warrant that:
              </p>
              <ul>
                <li>You provide accurate, current, and complete registration information (such as language levels and profile details).</li>
                <li>You are solely responsible for maintaining the confidentiality of your credentials.</li>
                <li>You are responsible for all activities conducted under your user account.</li>
                <li>You will immediately notify FaceTalk support of any unauthorized use of your account.</li>
              </ul>
            </section>

            <section id="purpose">
              <h2>3. Platform Purpose & Educational Scope</h2>
              <p>
                FaceTalk is an educational and cultural social platform designed strictly to assist users in learning languages, exchanging cultural knowledge, practicing verbal conversation, and providing peer or tutor language corrections.
              </p>
              <p>
                FaceTalk is <strong>not</strong> a dating service, financial network, or political propaganda outlet. Any attempts to repurpose FaceTalk for commercial solicitation, dating solicitation, scamming, or illegal trade violate these Terms and will result in immediate permanent account termination.
              </p>
            </section>

            <section id="code-of-conduct">
              <h2>4. Community Guidelines & Anti-Harassment</h2>
              <p>
                Our community is built on mutual respect, kindness, and cultural curiosity. When interacting in text chats, Moments feeds, or Voice Rooms, you agree not to:
              </p>
              <ul>
                <li>Harass, intimidate, bully, threaten, or demean any user based on race, ethnicity, nationality, gender, religion, sexual orientation, or language fluency level.</li>
                <li>Post or transmit sexually explicit, violent, obscene, defamatory, or unlawful materials.</li>
                <li>Spam users with repetitive messages, external affiliate links, cryptocurrency schemes, or marketing campaigns.</li>
                <li>Impersonate any person, educator, administrator, or organization.</li>
                <li>Attempt to reverse-engineer, decompile, or extract the source code of the FaceTalk app.</li>
              </ul>
            </section>

            <section id="voice-rooms">
              <h2>5. Voice Rooms & Audio Etiquette</h2>
              <p>
                FaceTalk provides interactive Drop-in Voice Rooms for audio conversations. When participating in Voice Rooms:
              </p>
              <ul>
                <li>Hosts have the authority to moderate room speakers, mute disruptive participants, or remove members violating room guidelines.</li>
                <li>You may not broadcast copyrighted music, offensive audio, or unauthorized third-party recordings.</li>
                <li>Users may not record other participants' voices without their explicit prior consent.</li>
              </ul>
            </section>

            <section id="subscriptions">
              <h2>6. In-App VIP Memberships & Payments</h2>
              <p>
                While core language exchange features are free, FaceTalk offers an optional VIP subscription providing premium perks (such as unlimited translations, advanced partner search filters, and room hosting badges).
              </p>
              <p>
                Subscriptions purchased through third-party application stores (e.g., Apple App Store or Google Play Store) are subject to the payment terms of the respective platform. Subscriptions automatically renew unless cancelled at least 24 hours prior to the end of the current billing cycle. Please review our <Link to="/refund"><strong>Refund Policy</strong></Link> for comprehensive refund conditions.
              </p>
            </section>

            <section id="intellectual-prop">
              <h2>7. Intellectual Property Rights</h2>
              <p>
                The FaceTalk name, logo, visual design, custom UI components, software algorithms, and trademarks are the exclusive property of FaceTalk and its licensors.
              </p>
              <p>
                You retain ownership of any text, audio snippets, or pictures you upload to your profile or Moments feed ("User Content"). By submitting User Content, you grant FaceTalk a worldwide, non-exclusive, royalty-free license to store, display, and transmit such content solely for the purpose of operating and enhancing the platform.
              </p>
            </section>

            <section id="termination">
              <h2>8. Account Termination & Suspension</h2>
              <p>
                We reserve the right to suspend or permanently terminate your account at our sole discretion, without prior notice or liability, if we determine that you have violated these Terms or engaged in conduct detrimental to other community members.
              </p>
            </section>

            <section id="disclaimer">
              <h2>9. Disclaimers & Limitation of Liability</h2>
              <p>
                FaceTalk is provided on an "AS IS" and "AS AVAILABLE" basis without warranties of any kind. While we actively moderate user accounts, we do not guarantee the fluency, identity, or behavior of any user or partner you encounter on the platform.
              </p>
              <p>
                To the maximum extent permitted by applicable law, FaceTalk shall not be liable for any indirect, incidental, punitive, or consequential damages resulting from your use of the application.
              </p>
            </section>

            <section id="contact">
              <h2>10. Contact Information</h2>
              <p>
                If you have questions, feedback, or concerns regarding these Terms & Conditions, please contact our legal and support team:
              </p>
              <div className="legal-box-highlight">
                <p><strong>FaceTalk Legal & Support Department</strong></p>
                <p>Email: <code>support@facetalk-app.com</code> / <code>legal@facetalk-app.com</code></p>
                <p>Platform: FaceTalk LMS Mobile Application</p>
              </div>
            </section>
          </main>
        </div>
      </div>
    </div>
  )
}
