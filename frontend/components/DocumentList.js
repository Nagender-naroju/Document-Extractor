// components/DocumentCard.jsx
import DataTable from "./DataTable";

export default function DocumentCard({ doc }) {
  if (!doc) return null;

  const extracted = doc.extracted_data;

  return (
    <div className="border p-4 rounded shadow">
      <p className="text-sm text-gray-500 mb-2">
        Status: {doc.status}
      </p>

      {!extracted ? (
        <p className="text-gray-400">No data available</p>
      ) : (
        <>
          {/* 🔥 Render EVERYTHING dynamically */}
          <DataTable data={extracted} />
        </>
      )}
    </div>
  );
}