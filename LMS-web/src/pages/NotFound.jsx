import React from 'react'
import { Link } from 'react-router-dom'
import { IconArrowRight } from '../components/common/Icons.jsx'

export default function NotFound() {
  return (
    <section className="section" style={{ minHeight: '65vh', display: 'flex', alignItems: 'center' }}>
      <div className="container" style={{ textAlign: 'center', maxWidth: '560px' }}>
        <div style={{
          fontSize: '72px',
          fontWeight: '900',
          color: 'var(--color-primary)',
          lineHeight: '1',
          marginBottom: '16px',
          fontFamily: 'var(--font-heading)'
        }}>
          404
        </div>
        <h1 style={{ fontSize: '28px', fontWeight: '800', marginBottom: '12px' }}>
          Page Not Found
        </h1>
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '32px', fontSize: '16px' }}>
          The page or policy document you are looking for doesn't exist or has moved.
        </p>
        <div style={{ display: 'flex', gap: '12px', justifyContent: 'center', flexWrap: 'wrap' }}>
          <Link to="/" className="btn btn-primary">
            <span>Return to FaceTalk Home</span>
            <IconArrowRight size={16} />
          </Link>
          <Link to="/terms" className="btn btn-secondary">
            <span>View Terms & Policies</span>
          </Link>
        </div>
      </div>
    </section>
  )
}
