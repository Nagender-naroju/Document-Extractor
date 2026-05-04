"use client";
import ProgressBar from "./ProgressBar";
import DataTable from "./DataTable";
import "./DocumentCard.css";

export default function DocumentCard({ doc }) {
  const badgeClass =
    doc.status === "processing"
      ? "badge-processing"
      : doc.status === "done"
      ? "badge-done"
      : "badge-failed";

  return (
    <div className="doc-card">
      <div className="doc-header">
        <div className="doc-icon">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" y1="13" x2="8" y2="13" />
            <line x1="16" y1="17" x2="8" y2="17" />
          </svg>
        </div>

        <div className="doc-info">
          <p className="doc-title">{doc.name}</p>

          {/* ✅ Document Type */}
          {doc.extracted_data?.document_type && (
            <p className="doc-type">
              {doc.extracted_data.document_type.replace(/_/g, " ")}
            </p>
          )}

          {doc.subtitle && (
            <p className="doc-subtitle">{doc.subtitle}</p>
          )}
        </div>

        <span className={`doc-badge ${badgeClass}`}>
          <span className="badge-dot" />
          {doc.status}
        </span>
      </div>

      {/* Progress */}
      {doc.status === "processing" && (
        <div className="doc-progress-section">
          <ProgressBar progress={doc.progress ?? 0} />
        </div>
      )}

      {/* Extracted Data */}
      {doc.extracted_data && (
        <div className="doc-data-section">
          {Object.entries(doc.extracted_data).map(([group, fields]) =>
            typeof fields === "object" && !Array.isArray(fields) ? (
              <DataTable
                key={group}
                data={fields}
                groupLabel={group.replace(/_/g, " ")}
              />
            ) : null
          )}
        </div>
      )}
    </div>
  );
}