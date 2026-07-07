const TESTIMONIALS = [
  { text: '"BlueWorx has completely transformed how we manage our website. The tools are powerful and the support team is incredibly responsive."', initials: "SJ", name: "Sarah Johnson", role: "Owner, Fresh Bakery Co." },
  { text: '"The live chat and booking system have increased our conversion rate significantly. Worth every penny — and then some."', initials: "MR", name: "Marcus Reid", role: "Director, Reid Consulting" },
  { text: '"Finally, one platform that does it all. We cancelled three separate subscriptions when we switched to BlueWorx."', initials: "AL", name: "Amy Leung", role: "Founder, Leung Law Group" },
];

export default function Testimonials({ style }: { style?: React.CSSProperties }) {
  return (
    <section className="sec" style={style}>
      <div className="center-head" style={{ marginBottom: 40 }}>
        <div className="eyebrow" style={{ marginBottom: 20 }}>What our clients say</div>
        <h2 className="h2">Kind words from our customers</h2>
      </div>
      <div className="tg">
        {TESTIMONIALS.map((t) => (
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
