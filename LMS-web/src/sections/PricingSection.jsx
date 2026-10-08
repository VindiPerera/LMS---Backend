import React from 'react'
import { IconSparkles, IconCheck, IconLock } from '../components/common/Icons.jsx'
import './PricingSection.css'

const pricingFeatures = [
  { label: 'Unlimited AI Translation & Original Subtitles', free: '5 times/day', vip: 'Unlimited' },
  { label: 'Unlock Visitors Page', free: false, vip: true },
  { label: 'Search Nearby Users', free: false, vip: true },
  { label: '9x Exposure Boost', free: false, vip: 'Up to 9x' },
  { label: 'Meet More Native Speakers', free: '1 of each', vip: '3 of each' },
  { label: 'Unlimited Live & Voice Room', free: false, vip: true },
  { label: 'Search Around the World', free: false, vip: true },
  { label: 'Filter Partners by Gender', free: false, vip: true },
  { label: 'Get More Language Partners', free: '10/day', vip: '25/day' },
  { label: 'View Nearby Moments', free: false, vip: true },
]

function PlanCell({ value, vip = false }) {
  if (value === true) {
    return (
      <span className={`pricing-cell-icon ${vip ? 'pricing-cell-icon--unlocked' : ''}`}>
        <IconCheck size={14} />
      </span>
    )
  }
  if (value === false) {
    return (
      <span className="pricing-cell-icon pricing-cell-icon--locked">
        <IconLock size={13} />
      </span>
    )
  }
  return <span className={`pricing-cell-text ${vip ? 'pricing-cell-text--vip' : ''}`}>{value}</span>
}

export default function PricingSection() {
  return (
    <section id="pricing" className="section pricing-section">
      <div className="container container-narrow">
        <div className="section-header">
          <div className="section-tag">
            <IconSparkles size={14} />
            <span>VIP Membership</span>
          </div>
          <h2 className="section-title">Simple, Transparent Pricing</h2>
          <p className="section-subtitle">
            Start speaking for free, or upgrade to VIP for unlimited translations, priority exposure,
            and more ways to connect with language partners worldwide.
          </p>
        </div>

        <div className="pricing-table-scroll">
          <div className="pricing-table">
            <div className="pricing-table__glow" />

            <div className="pricing-table__header">
              <div className="pricing-table__feature-col" />
              <div className="pricing-table__plan-col">
                <span className="pricing-plan-label">Non-VIP</span>
              </div>
              <div className="pricing-table__plan-col pricing-table__plan-col--vip">
                <span className="pricing-vip-ribbon">Best Value</span>
                <span className="pricing-plan-label pricing-plan-label--vip">
                  <IconSparkles size={13} /> VIP
                </span>
              </div>
            </div>

            {pricingFeatures.map((feature) => (
              <div className="pricing-table__row" key={feature.label}>
                <div className="pricing-table__feature">{feature.label}</div>
                <div className="pricing-table__value">
                  <PlanCell value={feature.free} />
                </div>
                <div className="pricing-table__value pricing-table__value--vip">
                  <PlanCell value={feature.vip} vip />
                </div>
              </div>
            ))}

            <div className="pricing-table__cta-row">
              <div className="pricing-table__feature-col" />
              <div className="pricing-table__plan-col" />
              <div className="pricing-table__plan-col pricing-table__plan-col--vip">
                <a href="/#download" className="btn btn-primary btn-lg pricing-subscribe-btn">
                  <IconSparkles size={16} />
                  <span>Subscribe — Rs. 1,000.00 / 30 days</span>
                </a>
                <span className="pricing-table__fx-note">LKR &middot; billed every 30 days</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
