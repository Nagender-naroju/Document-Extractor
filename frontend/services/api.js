// services/api.js
import axios from "axios";

const API = "http://localhost:8000/api";

// ✅ Upload API
export const uploadDocuments = async (files) => {
  const formData = new FormData();

  files.forEach((file) => {
    formData.append("documents[]", file);
  });

  return axios.post(`${API}/upload`, formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });
};

// ✅ GET DOCUMENTS (THIS WAS MISSING ❗)
export const getDocuments = async () => {
  return axios.get(`${API}/documents`);
};