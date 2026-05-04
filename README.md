# Document-Extractor


# Document Extraction System

A full-stack application to extract structured data from documents (images, PDFs, DOCX) using OCR and AI.

---

##  Tech Stack

### Backend

* Laravel (Queue-based processing)
* Tesseract OCR (text extraction)
* Ollama (local LLM for data extraction)

### Frontend

* Next.js (UI)
* Custom components for document preview & extracted data

---

## How It Works

1. User uploads a document
2. Laravel Job is dispatched
3. OCR extracts raw text using Tesseract
4. Text is sent to Ollama (LLM)
5. AI converts raw text → structured JSON
6. Frontend displays extracted data

---

## Installation

### 1. Clone Repo

```bash
git clone https://github.com/your-username/your-repo.git
cd your-repo
```

---

### 2. Backend Setup (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

---

### 3. Install Tesseract

#### Ubuntu:

```bash
sudo apt install tesseract-ocr
```

#### Windows:

Download from:
https://github.com/tesseract-ocr/tesseract

---

### 4. Install Ollama

Download & install:
https://ollama.com

Pull model:

```bash
ollama pull llama3.2
```

Run server:

```bash
ollama serve
```

---

### 5. Run Queue Worker

```bash
php artisan queue:work
```

---

### 6. Frontend Setup (Next.js)

```bash
cd frontend
npm install
npm run dev
```

---

## Features

* Supports images, PDFs, DOCX
* Automatic document type detection
* Extracts structured data (JSON)
* Background processing using Laravel Jobs
* Real-time UI updates (progress + results)

---

## Important Note (AI Accuracy)

This project uses **local AI (Ollama)**:

* Free and runs offline
* But **not always accurate or consistent**

###  For production-grade accuracy:

You should use paid APIs like:

* OpenAI (GPT)
* Anthropic (Claude)

These provide:

* Better JSON consistency
* Higher accuracy
* Faster responses

---

##  Why Not Fully Real-Time?

Local models:

* Slower
* Limited hardware dependent
* No guaranteed structured output

 For real-time and reliable systems, use:

* OpenAI API
* Anthropic API

---

##  Folder Structure

```
backend/   → Laravel API + Jobs
frontend/  → Next.js UI
```

---

## Future Improvements

* Streaming responses
* Better document classification
* Multi-language OCR
* Replace Ollama with OpenAI/Claude for production

---

##  Author

Nagender Naroju
---
