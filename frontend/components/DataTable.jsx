import "./DataTable.css";

export default function DataTable({ data, groupLabel }) {
  if (!data) return null;

  const renderValue = (value) => {
    if (value === null || value === undefined || value === "") {
      return <span className="dt-na">N/A</span>;
    }
    if (Array.isArray(value)) {
      return (
        <div className="dt-pills">
          {value.map((item, i) => (
            <span key={i} className="dt-pill">{String(item)}</span>
          ))}
        </div>
      );
    }
    if (typeof value === "object") {
      return (
        <div className="dt-nested">
          <DataTable data={value} />
        </div>
      );
    }
    return <span>{value.toString()}</span>;
  };

  return (
    <div className="dt-group">
      {groupLabel && <div className="dt-group-label">{groupLabel}</div>}
      {Object.entries(data).map(([key, value]) => (
        <div key={key} className="dt-row">
          <span className="dt-key">{key.replace(/_/g, " ")}</span>
          <span className="dt-val">{renderValue(value)}</span>
        </div>
      ))}
    </div>
  );
}