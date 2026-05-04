"use client";

import UploadBox from "@/components/UploadBox";
import FileList from "@/components/FileList";
import useDocuments from "@/hooks/useDocuments";

export default function Dashboard() {
  const { documents, fetchDocs } = useDocuments();

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">
        AI Document Dashboard
      </h1>

      <UploadBox onUploadSuccess={fetchDocs} />

      <FileList documents={documents} />
    </div>
  );
}