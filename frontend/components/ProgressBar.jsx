import "./ProgressBar.css";

export default function ProgressBar({ progress = 0, label = "Extracting data…" }) {
  const pct = Math.min(100, Math.max(0, Math.round(progress)));
  return (
    <div className="pb-wrapper">
      <div className="pb-labels">
        <span>{label}</span>
        <span>{pct}%</span>
      </div>
      <div className="pb-track">
        <div className="pb-fill" style={{ width: `${pct}%` }} />
      </div>
    </div>
  );
}