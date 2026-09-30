import React, { useState } from 'react'
import {
  IconSmartphone,
  IconQrCode,
  IconCheck,
  IconShield,
  IconSparkles,
} from '../components/common/Icons.jsx'
import appIcon from '../assets/images/facetalk_app_icon.png'
import './CtaSection.css'

export default function CtaSection() {
  const [downloadModal, setDownloadModal] = useState(false)

  return (
    <section id="download" className="section cta-section">
      <div className="container">
        <div className="cta-card">
          <div className="cta-card__glow" />

          <div className="cta-card__content">
            <div className="cta-card__badge">
              <IconSparkles size={14} />
              <span>Start Speaking Today</span>
            </div>

            <h2 className="cta-card__title">
              Ready to Practice Real Conversations?
            </h2>

            <p className="cta-card__desc">
              Join more than 500,000 language learners and native speakers. Download FaceTalk free and unlock a world of authentic connections.
            </p>

            <div className="cta-card__buttons">
              <button
                type="button"
                className="btn btn-primary btn-lg"
                onClick={() => setDownloadModal(true)}
              >
                <IconSmartphone size={20} />
                <span>Download FaceTalk Mobile</span>
              </button>

              <button
                type="button"
                className="btn btn-secondary btn-lg"
                onClick={() => setDownloadModal(true)}
              >
                <IconQrCode size={20} />
                <span>Scan QR Code</span>
              </button>
            </div>

            <div className="cta-card__perks">
              <span className="cta-perk">
                <IconCheck size={14} className="text-green" /> Free Forever Tier
              </span>
              <span className="cta-perk">
                <IconShield size={14} className="text-green" /> Zero Spam & Verified Safe
              </span>
              <span className="cta-perk">
                <IconCheck size={14} className="text-green" /> iOS & Android Ready
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Download Modal / QR Dialog */}
      {downloadModal && (
        <div className="download-modal-overlay" onClick={() => setDownloadModal(false)}>
          <div className="download-modal" onClick={(e) => e.stopPropagation()}>
            <button
              type="button"
              className="download-modal__close"
              onClick={() => setDownloadModal(false)}
            >
              ✕
            </button>
            <img src={appIcon} alt="FaceTalk App" className="download-modal__app-icon" />
            <h3 className="download-modal__title">Get FaceTalk for Mobile</h3>
            <p className="download-modal__subtitle">
              Scan the QR code with your smartphone camera to install the mobile app directly.
            </p>

            {/* Stylized QR Code placeholder */}
            <div className="download-modal__qr-frame">
              <div className="qr-simulated">
                <div className="qr-box top-left" />
                <div className="qr-box top-right" />
                <div className="qr-box bottom-left" />
                <div className="qr-center-icon">
                  <img src={appIcon} alt="App" width="28" height="28" />
                </div>
              </div>
            </div>

            <div className="download-modal__store-badges">
              <div className="store-badge">
                <span className="store-badge__icon"></span>
                <div>
                  <div className="store-badge__sub">Download on the</div>
                  <div className="store-badge__name">Apple App Store</div>
                </div>
              </div>
              <div className="store-badge">
                <span className="store-badge__icon">▶</span>
                <div>
                  <div className="store-badge__sub">GET IT ON</div>
                  <div className="store-badge__name">Google Play</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </section>
  )
}
