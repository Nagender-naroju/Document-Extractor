"use client";

import { useEffect, useState } from "react";
import { getDocuments } from "@/services/api";

export default function useDocuments() {
  const [documents, setDocuments] = useState([]);

  const fetchDocs = async () => {
    try {
      const res = await getDocuments();
      setDocuments(res.data);
    } catch (err) {
      console.error("Error fetching documents:", err);
    }
  };

  useEffect(() => {
    fetchDocs();
  }, []);

  return { documents, fetchDocs };
}