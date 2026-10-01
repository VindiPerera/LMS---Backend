import React, { useState } from 'react'
import { faqs } from '../data/contentData.js'
import { IconChevronDown, IconSparkles } from '../components/common/Icons.jsx'
import './FaqSection.css'

export default function FaqSection() {
  const [openIndex, setOpenIndex] = useState(0)

  const toggleFaq = (index) => {
    setOpenIndex(openIndex === index ? null : index)
  }

  return (
    <section id="faq" className="section faq-section">
      <div className="container container-narrow">
        <div className="section-header">
          <div className="section-tag">
            <IconSparkles size={14} />
            <span>Got Questions?</span>
          </div>
          <h2 className="section-title">Frequently Asked Questions</h2>
          <p className="section-subtitle">
            Everything you need to know about FaceTalk's purpose, community, features, and policies.
          </p>
        </div>

        <div className="faq-list">
          {faqs.map((faq, index) => {
            const isOpen = openIndex === index
            return (
              <div
                key={index}
                className={`faq-item ${isOpen ? 'faq-item--open' : ''}`}
                onClick={() => toggleFaq(index)}
              >
                <div className="faq-question">
                  <span className="faq-question-text">{faq.question}</span>
                  <span className={`faq-icon ${isOpen ? 'faq-icon--rotated' : ''}`}>
                    <IconChevronDown size={18} />
                  </span>
                </div>
                {isOpen && (
                  <div className="faq-answer">
                    <p>{faq.answer}</p>
                  </div>
                )}
              </div>
            )
          })}
        </div>
      </div>
    </section>
  )
}
