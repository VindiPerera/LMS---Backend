import { useState, useEffect } from 'react'
import { Link, useLocation } from 'react-router-dom'
import logo from '../../assets/images/facetalk_logo.png'
import { IconMenu, IconClose, IconSparkles } from '../common/Icons.jsx'
import './Layout.css'

export default function Header() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const location = useLocation()

  useEffect(() => {
    const handleScroll = () => {
      setScrolled(window.scrollY > 20)
    }
    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  // Close mobile drawer on route change
  useEffect(() => {
    setMobileMenuOpen(false)
  }, [location])

  return (
    <header className={`header ${scrolled ? 'header--scrolled' : ''}`}>
      <div className="container header__inner">
        <Link to="/" className="header__brand" aria-label="FaceTalk Home">
          <img src={logo} alt="FaceTalk" className="header__logo-img" />
          <span className="header__brand-sub">Language Exchange</span>
        </Link>

        {/* Desktop Navigation */}
        <nav className="header__nav" aria-label="Main Navigation">
          <a href="/#features" className="header__link">Features</a>
          <a href="/#audience" className="header__link">Who It's For</a>
          <a href="/#how-it-works" className="header__link">How It Works</a>
          <a href="/#pricing" className="header__link">Pricing</a>
          <a href="/#faq" className="header__link">FAQ</a>
          <Link to="/contact" className="header__link">Contact</Link>
          <div className="header__dropdown">
            <span className="header__link header__dropdown-toggle">
              Legal & Policies
            </span>
            <div className="header__dropdown-menu">
              <Link to="/terms" className="header__dropdown-item">Terms & Conditions</Link>
              <Link to="/privacy" className="header__dropdown-item">Privacy Policy</Link>
              <Link to="/refund" className="header__dropdown-item">Refund Policy</Link>
            </div>
          </div>
        </nav>

        {/* Action Button */}
        <div className="header__actions">
          <a href="/#download" className="btn btn-primary btn-sm">
            <IconSparkles size={15} />
            <span>Get FaceTalk</span>
          </a>

          <button
            type="button"
            className="header__mobile-toggle"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            aria-label="Toggle navigation menu"
          >
            {mobileMenuOpen ? <IconClose size={22} /> : <IconMenu size={22} />}
          </button>
        </div>
      </div>

      {/* Mobile Drawer */}
      {mobileMenuOpen && (
        <div className="header__mobile-menu">
          <div className="header__mobile-links">
            <a href="/#features" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">Features</a>
            <a href="/#audience" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">Who It's For</a>
            <a href="/#how-it-works" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">How It Works</a>
            <a href="/#pricing" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">Pricing</a>
            <a href="/#faq" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">FAQ</a>
            <Link to="/contact" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">Contact</Link>

            <div className="header__mobile-divider" />
            <div className="header__mobile-section-label">Legal Policies</div>
            <Link to="/terms" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">Terms & Conditions</Link>
            <Link to="/privacy" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">Privacy Policy</Link>
            <Link to="/refund" onClick={() => setMobileMenuOpen(false)} className="header__mobile-link">Refund Policy</Link>

            <div className="header__mobile-cta">
              <a href="/#download" onClick={() => setMobileMenuOpen(false)} className="btn btn-primary" style={{ width: '100%' }}>
                <IconSparkles size={16} />
                <span>Download FaceTalk App</span>
              </a>
            </div>
          </div>
        </div>
      )}
    </header>
  )
}
