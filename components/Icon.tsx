import { ICONS } from "@/lib/icons";

// Matches the design runtime's data-ic injection: a stroked 24x24 lucide-style
// glyph that fills its parent box.
export default function Icon({
  name,
  className,
  style,
}: {
  name: string;
  className?: string;
  style?: React.CSSProperties;
}) {
  const path = ICONS[name];
  if (!path) return null;
  return (
    <span data-ic={name} className={className} style={style}>
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        style={{ width: "100%", height: "100%" }}
        dangerouslySetInnerHTML={{ __html: path }}
      />
    </span>
  );
}
