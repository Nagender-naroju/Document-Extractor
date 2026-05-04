import { useState } from "react";
import API from "../services/api";

export default function UploadForm() {
  const [files, setFiles] = useState([]);

  const upload = async () => {
    const formData = new FormData();

    Array.from(files).forEach(file => {
      formData.append("documents[]", file);
    });

    await API.post("/upload", formData);
    alert("Uploaded");
  };

  return (
    <div>
      <input type="file" multiple onChange={(e) => setFiles(e.target.files)} />
      <button onClick={upload}>Upload</button>
    </div>
  );
}