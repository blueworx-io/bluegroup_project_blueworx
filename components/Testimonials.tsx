import { getTestimonials } from "@/lib/api/content";

export default async function Testimonials({ style }: { style?: React.CSSProperties }) {
  const testimonials = await getTestimonials();
  return (
    <section className="sec" style={style}>
      <div className="center-head" style={{ marginBottom: 40 }}>
        <div className="eyebrow" style={{ marginBottom: 20 }}>What our clients say</div>
        <h2 className="h2">Kind words from our customers</h2>
      </div>
      <div className="tg">
        {testimonials.map((t) => (
          <div key={t.name} className="tc">
            <div className="tstars">★★★★★</div>
            <p className="ttext">{t.text}</p>
            <div className="tauthor">
              <div className="tavatar">{t.initials}</div>
              <div>
                <div className="tname">{t.name}</div>
                <div className="trole">{t.role}</div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
