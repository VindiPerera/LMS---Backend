import React, { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { IconRefresh, IconCheck, IconShield } from '../components/common/Icons.jsx'
import './LegalPage.css'

export default function Refund() {
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
            <IconRefresh size={14} />
            <span>Billing Transparency</span>
          </div>
          <h1 className="legal-header__title">Refund & Subscription Policy</h1>
          <div className="legal-header__meta">
            <span>Last Updated: September 2026</span>
            <span>•</span>
            <span>Applies to FaceTalk VIP Memberships & In-App Purchases</span>
          </div>
        </div>

        {/* Content Layout */}
        <div className="legal-layout">
          {/* Sidebar */}
          <aside className="legal-sidebar">
            <div className="legal-sidebar__title">Table of Contents</div>
            <nav className="legal-sidebar__nav">
              <a href="#overview" className="legal-sidebar__link">1. Refund Policy Overview</a>
              <a href="#free-trial" className="legal-sidebar__link">2. Free Features vs VIP Tier</a>
              <a href="#cooling-off" className="legal-sidebar__link">3. 14-Day Cooling-off Period</a>
              <a href="#apple-store" className="legal-sidebar__link">4. Apple App Store Purchases</a>
              <a href="#google-play" className="legal-sidebar__link">5. Google Play Purchases</a>
              <a href="#cancellation" className="legal-sidebar__link">6. How to Cancel Renewal</a>
              <a href="#non-refundable" className="legal-sidebar__link">7. Non-Refundable Situations</a>
              <a href="#processing" className="legal-sidebar__link">8. Processing Times</a>
              <a href="#contact" className="legal-sidebar__link">9. Billing Support Contact</a>
            </nav>

            <div className="legal-sidebar__other-policies">
              <span className="legal-sidebar__other-title">Other Policies</span>
              <Link to="/terms" className="legal-sidebar__other-link">Terms & Conditions ➔</Link>
              <Link to="/privacy" className="legal-sidebar__other-link">Privacy Policy ➔</Link>
            </div>
          </aside>

          {/* Main Content */}
          <main className="legal-content">
            <div className="legal-box-highlight">
              <p>
                <strong>Fair & Transparent Billing.</strong> We want you to love your FaceTalk experience. If you are not satisfied with your VIP membership, we provide clear refund pathways in accordance with App Store and Google Play guidelines.
              </p>
            </div>

            <section id="overview">
              <h2>1. Refund Policy Overview</h2>
              <p>
                This policy outlines the conditions under which refunds are granted for digital subscriptions and virtual items purchased within the FaceTalk mobile application.
              </p>
            </section>

            <section id="free-trial">
              <h2>2. Free Features vs VIP Tier</h2>
              <p>
                Please remember that FaceTalk’s core features — including 1-on-1 language messaging, community Voice Rooms, Moments viewing, and peer corrections — are 100% free to use forever.
              </p>
              <p>
                VIP memberships grant extra privileges such as unlimited daily machine translations, advanced user search filters, VIP profile badges, and elevated room hosting tools. We encourage trying all free features before purchasing VIP.
              </p>
            </section>

            <section id="cooling-off">
              <h2>3. 14-Day Satisfaction Guarantee</h2>
              <p>
                For eligible direct subscriptions, we offer a 14-day refund window from the date of initial purchase if you find that FaceTalk VIP does not meet your learning expectations.
              </p>
            </section>

            <section id="apple-store">
              <h2>4. Apple App Store Purchases (iOS)</h2>
              <p>
                Apple manages all billing transactions and refunds for purchases made on iOS devices. FaceTalk developers do not possess the administrative authority to issue direct refunds for transactions billed through Apple ID.
              </p>
              <p><strong>To request a refund from Apple:</strong></p>
              <ol>
                <li>Visit Apple’s official portal: <a href="https://reportaproblem.apple.com" target="_blank" rel="noreferrer" style={{ color: 'var(--color-primary)', textDecoration: 'underline' }}>reportaproblem.apple.com</a>.</li>
                <li>Sign in with the Apple ID used to purchase the subscription.</li>
                <li>Select "I'd like to..." and choose <strong>"Request a refund"</strong>.</li>
                <li>Select the reason and pick your FaceTalk VIP purchase.</li>
                <li>Submit your claim. Apple typically reviews claims within 48 hours.</li>
              </ol>
            </section>

            <section id="google-play">
              <h2>5. Google Play Store Purchases (Android)</h2>
              <p>
                For Android devices, purchases are processed through Google Play.
              </p>
              <p><strong>Within 48 hours of purchase:</strong></p>
              <ul>
                <li>You can request a direct refund directly via the Google Play website: open your Google Play Order History, select FaceTalk, and choose "Request a refund".</li>
              </ul>
              <p><strong>After 48 hours (within 14 days):</strong></p>
              <ul>
                <li>Send your Google Play Order Number (formatted as <code>GPA.XXXX-XXXX-XXXX-XXXXX</code>) along with your FaceTalk user ID to <code>billing@facetalk-app.com</code>. Our billing department will review your request.</li>
              </ul>
            </section>

            <section id="cancellation">
              <h2>6. How to Cancel Recurring Renewals</h2>
              <p>
                Cancelling a subscription prevents future recurring charges while keeping your VIP access active until the end of the current billing cycle.
              </p>
              <ul>
                <li>
                  <strong>On iOS:</strong> Open iPhone <code>Settings ➔ Tap your Name ➔ Subscriptions ➔ FaceTalk ➔ Cancel Subscription</code>.
                </li>
                <li>
                  <strong>On Android:</strong> Open <code>Google Play Store ➔ Tap Profile Icon ➔ Payments & Subscriptions ➔ Subscriptions ➔ FaceTalk ➔ Cancel Subscription</code>.
                </li>
              </ul>
            </section>

            <section id="non-refundable">
              <h2>7. Non-Refundable Situations</h2>
              <p>Refunds may be declined under the following circumstances:</p>
              <ul>
                <li>Accounts terminated due to severe violations of Community Guidelines (e.g., hate speech, harassment, scamming, or illegal conduct).</li>
                <li>Refund requests submitted past the applicable eligibility windows.</li>
                <li>Fraudulent abuse of refund mechanisms.</li>
              </ul>
            </section>

            <section id="processing">
              <h2>8. Processing Times</h2>
              <p>
                Once approved, refunds are credited back to the original payment method used at the time of purchase:
              </p>
              <ul>
                <li><strong>Credit / Debit Card:</strong> 3 to 7 business days depending on your bank.</li>
                <li><strong>PayPal / Digital Wallet:</strong> 24 to 48 hours.</li>
                <li><strong>Apple Store / Google Play Credit:</strong> 24 hours.</li>
              </ul>
            </section>

            <section id="contact">
              <h2>9. Billing Support Contact</h2>
              <p>If you encounter any unexpected billing issue, please contact our team:</p>
              <div className="legal-box-highlight">
                <p><strong>FaceTalk Billing Support</strong></p>
                <p>Email: <code>billing@facetalk-app.com</code></p>
                <p>Please include: Your FaceTalk Account Username, Registered Email, and Order Receipt number.</p>
              </div>
            </section>
          </main>
        </div>
      </div>
    </div>
  )
}
