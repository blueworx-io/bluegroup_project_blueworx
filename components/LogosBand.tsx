const BRANDS = ["QURE", "Padel365", "HIRASTÉ", "BlueWorx", "chromaesthesia"];

export default function LogosBand() {
  const marks = [...BRANDS, ...BRANDS];
  return (
    <div
      style={{
        margin: "48px 0",
        padding: "56px 0",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        gap: 34,
        borderTop: "1px solid #EFEFF0",
        borderBottom: "1px solid #EFEFF0",
        background: "#FCFCFD",
      }}
    >
      <div style={{ display: "flex", alignItems: "center", gap: 18, width: "100%", maxWidth: 720, padding: "0 32px", boxSizing: "border-box" }}>
        <span style={{ flex: 1, height: 1, background: "linear-gradient(90deg,transparent,#E3E4E8)" }}></span>
        <p style={{ margin: 0, fontSize: "12.5px", fontWeight: 600, letterSpacing: ".14em", textTransform: "uppercase", color: "#8A8F98", whiteSpace: "nowrap" }}>
          Loved by owners. Trusted by businesses.
        </p>
        <span style={{ flex: 1, height: 1, background: "linear-gradient(90deg,#E3E4E8,transparent)" }}></span>
      </div>
      <div className="logos-mask">
        <div className="logos-track" style={{ gap: 64 }}>
          {marks.map((name, i) => (
            <span key={i} className="brandmark">{name}</span>
          ))}
        </div>
      </div>
    </div>
  );
}
