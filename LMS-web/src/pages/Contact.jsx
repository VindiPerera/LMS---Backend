import React, { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { IconPhone, IconMail, IconMapPin, IconHeadphones } from '../components/common/Icons.jsx'
import './LegalPage.css'
import './Contact.css'

export default function Contact() {
  useEffect(() => {
    window.scrollTo(0, 0)
  }, [])

  return (
    <div className="contact-page">
      <div className="container">
        {/* Header */}
        <div className="legal-header">
          <Link to="/" className="legal-header__back">
            ← Back to FaceTalk Overview
          </Link>
          <div className="legal-header__tag">
            <IconHeadphones size={14} />
            <span>We're Here to Help</span>
          </div>
          <h1 className="legal-header__title">Contact Us</h1>
          <p className="contact-intro">
            Have a question about FaceTalk, your VIP membership, or a billing issue? Reach our team
            through any of the channels below and we'll get back to you as soon as possible.
          </p>
        </div>

        {/* Contact Cards */}
        <div className="contact-cards">
          <div className="contact-card">
            <span className="contact-card__icon">
              <IconPhone size={20} />
            </span>
            <h3 className="contact-card__title">Call Us</h3>
            <a href="tel:+94775559738" className="contact-card__value">+94 77 555 9738</a>
            <p className="contact-card__note">Available during business hours (Sri Lanka Time)</p>
          </div>

          <div className="contact-card">
            <span className="contact-card__icon">
              <IconMail size={20} />
            </span>
            <h3 className="contact-card__title">Email Us</h3>
            <a href="mailto:facetalk87@gmail.com" className="contact-card__value">facetalk87@gmail.com</a>
            <p className="contact-card__note">General inquiries, support & billing</p>
          </div>

          <div className="contact-card">
            <span className="contact-card__icon">
              <IconMapPin size={20} />
            </span>
            <h3 className="contact-card__title">Our Address</h3>
            <p className="contact-card__value contact-card__value--address">
              37/9, Mahakatuwana Rd, Homagama, Western Province, Sri Lanka
            </p>
          </div>
        </div>

        <div className="legal-box-highlight contact-support-note">
          <p>
            <strong>FaceTalk Support Team</strong> — For VIP membership, payment, or refund-related
            questions, please see our{' '}
            <Link to="/refund" style={{ color: 'var(--color-primary)', fontWeight: 600 }}>Refund Policy</Link>{' '}
            or contact us directly using the details above.
          </p>
        </div>
      </div>
    </div>
  )
}
