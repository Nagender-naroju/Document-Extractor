"use client";

import { useState } from "react";
import { uploadDocuments } from "@/services/api";

export default function UploadBox({ onUploadSuccess }) {
  const [files, setFiles] = useState([]);

  const handleUpload = async () => {
    if (!files.length) return alert("Select files first");

    try {
      await uploadDocuments(files);
      onUploadSuccess();
    } catch (err) {
      console.error("Upload error:", err);
    }
  };

  return (
    <div className="border p-4 mb-6">
      <input
        type="file"
        multiple
        onChange={(e) => setFiles([...e.target.files])}
      />

      <button
        onClick={handleUpload}
        className="bg-blue-500 text-white px-4 py-2 mt-2"
      >
        Upload
      </button>
    </div>
  );
}