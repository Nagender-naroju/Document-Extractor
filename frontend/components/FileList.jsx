import DocumentCard from "./DocumentCard";

export default function FileList({ documents }) {
  if (!documents.length) return <p>No documents uploaded</p>;

  return (
    <div className="grid grid-cols-3 gap-4">
      {documents.map((doc) => (
        <DocumentCard key={doc.id} doc={doc} />
      ))}
    </div>
  );
}