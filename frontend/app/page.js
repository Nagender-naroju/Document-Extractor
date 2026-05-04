import UploadForm from "../components/UploadForm";
import DocumentList from "../components/DocumentList";

export default function Home() {
  return (
    <div>
      <h1>AI Document Extractor</h1>
      <UploadForm />
      <DocumentList />
    </div>
  );
}