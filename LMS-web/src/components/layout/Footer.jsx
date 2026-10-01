import { Link } from 'react-router-dom'
import logo from '../../assets/images/facetalk_logo.png'
import appIcon from '../../assets/images/facetalk_app_icon.png'
import { IconShield, IconGlobe } from '../common/Icons.jsx'
import './Layout.css'

export default function Footer() {
  return (
    <footer className="footer">
      <div className="container footer__container">
        <div className="footer__top">
          {/* Brand Info */}
          <div className="footer__brand-col">
            <Link to="/" className="footer__brand">
              <img src={logo} alt="FaceTalk" className="footer__logo-img" />
            </Link>
            <p className="footer__tagline">
              A modern language exchange and social learning mobile application empowering students, educators, and global learners to speak naturally and connect worldwide.
            </p>
            <div className="footer__app-badge">
              <img src={appIcon} alt="App Icon" className="footer__app-icon" />
              <div>
                <div className="footer__app-title">FaceTalk LMS Mobile</div>
                <div className="footer__app-sub">Available on iOS & Android</div>
              </div>
            </div>
          </div>

          {/* Product Links */}
          <div className="footer__col">
            <h4 className="footer__heading">Platform</h4>
            <ul className="footer__list">
              <li><a href="/#features">Voice Rooms</a></li>
              <li><a href="/#features">In-Line Corrections</a></li>
              <li><a href="/#features">1-on-1 Language Swap</a></li>
              <li><a href="/#features">Global Moments</a></li>
              <li><a href="/#audience">Fluency Streaks</a></li>
            </ul>
          </div>

          {/* Target Audience */}
          <div className="footer__col">
            <h4 className="footer__heading">Who It's For</h4>
            <ul className="footer__list">
              <li><a href="/#audience">Academic Students</a></li>
              <li><a href="/#audience">Native Speakers</a></li>
              <li><a href="/#audience">Teachers & Tutors</a></li>
              <li><a href="/#audience">Expats & Travelers</a></li>
              <li><a href="/#audience">Exam Preparation</a></li>
            </ul>
          </div>

          {/* Legal & Compliance - Explicitly required */}
          <div className="footer__col">
            <h4 className="footer__heading">Legal & Policies</h4>
            <ul className="footer__list">
              <li>
                <Link to="/terms" className="footer__legal-link">
                  Terms & Conditions
                </Link>
              </li>
              <li>
                <Link to="/privacy" className="footer__legal-link">
                  Privacy Policy
                </Link>
              </li>
              <li>
                <Link to="/refund" className="footer__legal-link">
                  Refund Policy
                </Link>
              </li>
              <li>
                <span className="footer__badge-status">
                  <IconShield size={14} /> Safe Community
                </span>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="footer__bottom">
          <div className="footer__copyright">
            © {new Date().getFullYear()} FaceTalk Language Network. All rights reserved.
          </div>
          <div className="footer__meta">
            <span className="footer__meta-item">
              <IconGlobe size={14} /> Global Language Learning
            </span>
            <span className="footer__meta-divider">•</span>
            <Link to="/privacy">Data Privacy</Link>
            <span className="footer__meta-divider">•</span>
            <Link to="/terms">Terms</Link>
            <span className="footer__meta-divider">•</span>
            <Link to="/refund">Refunds</Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
