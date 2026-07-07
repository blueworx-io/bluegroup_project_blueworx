"use client";

import { useState } from "react";
import { FAQS } from "@/lib/data";

export default function FaqList() {
  const [open, setOpen] = useState(0);
  return (
    <div className="faq-list">
      {FAQS.map((f, i) => (
        <div key={f.q} className={open === i ? "faq-item open" : "faq-item"}>
          <div className="faq-q" onClick={() => setOpen(open === i ? -1 : i)}>
            {f.q}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <polyline points="6 9 12 15 18 9" />
            </svg>
          </div>
          <div className="faq-a">
            <p>{f.a}</p>
          </div>
        </div>
      ))}
    </div>
  );
}
