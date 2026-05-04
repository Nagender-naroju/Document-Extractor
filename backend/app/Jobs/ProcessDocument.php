<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Throwable;

class ProcessDocument implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 360; // 60 s buffer over the 300 s HTTP timeout

    private const MAX_TEXT_LENGTH  = 3000;
    private const OLLAMA_URL       = 'http://localhost:11434/api/chat';
    private const OLLAMA_MODEL     = 'llama3.1:latest';
    private const OLLAMA_MAX_TOKENS = 4096; // prevent truncated JSON responses

    public function __construct(protected Document $document) {}

    // =========================================================================
    // MAIN HANDLER
    // =========================================================================

    public function handle(): void
    {
        $path = storage_path('app/' . $this->document->file_path);

        if (!file_exists($path)) {
            $this->failJob("File not found: {$path}");
            return;
        }

        // 1. OCR
        $text = $this->extractText($path);

        if (empty(trim($text))) {
            $this->failJob("OCR returned empty text");
            return;
        }

        // 2. Clean text
        $text = $this->cleanText($text);

        $ocrQuality = $this->assessOcrQuality($text);
        Log::info("OCR LENGTH: " . strlen($text) . " | QUALITY: {$ocrQuality}");
        Log::debug("OCR PREVIEW: " . substr($text, 0, 300));

        // 3. LLM
        $raw = $this->callLlm($text);

        if ($raw === null || trim($raw) === '') {
            // Throw so the job retries (up to $tries times)
            throw new \RuntimeException("Empty LLM response — will retry");
        }

        Log::debug("LLM RAW: " . substr($raw, 0, 500));

        // 4. Parse JSON
        $data = $this->parseJson($raw);

        if (!$data) {
            // Non-fatal: store raw text so the record is not permanently lost
            Log::warning("JSON parse failed — storing raw fallback");
            $data = [
                'document_type' => 'parse_failed',
                'data'          => ['raw_text' => $text],
                'confidence'    => 0,
            ];
        }

        // 5. Save
        $this->document->update([
            'extracted_data' => $data,
            'status'         => 'completed',
        ]);

    }

    // =========================================================================
    // FAILED HOOK  — called by Laravel after all retries are exhausted
    // =========================================================================

    public function failed(Throwable $e): void
    {
        Log::error("ProcessDocument permanently failed: " . $e->getMessage());

        $this->document->update(['status' => 'failed']);
    }

    // =========================================================================
    // OCR
    // =========================================================================

    private function extractText(string $path): string
    {
        try {
            return (new TesseractOCR($path))->run();
        } catch (Throwable $e) {
            Log::error("OCR exception: " . $e->getMessage());
            return '';
        }
    }

    // =========================================================================
    // TEXT CLEANING
    // =========================================================================

    private function cleanText(string $text): string
    {
        // Strip non-printable control characters (keep newlines)
        $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text);

        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Truncate to avoid overwhelming the LLM context
        if (strlen($text) > self::MAX_TEXT_LENGTH) {
            $text = substr($text, 0, self::MAX_TEXT_LENGTH);
        }

        return trim($text);
    }

    // =========================================================================
    // OCR QUALITY ASSESSMENT
    // =========================================================================

    /**
     * Returns 'good' | 'fair' | 'poor' based on character and word stats.
     */
    private function assessOcrQuality(string $text): string
    {
        $totalChars = strlen($text);

        if ($totalChars === 0) {
            return 'empty';
        }

        // Count non-ASCII / garbled characters
        preg_match_all('/[^\x20-\x7E]/', $text, $matches);
        $garbled      = count($matches[0]);
        $garbledRatio = $garbled / $totalChars;

        $wordCount  = str_word_count($text);
        $avgWordLen = $wordCount > 0 ? ($totalChars / $wordCount) : 0;

        if ($garbledRatio > 0.10 || $avgWordLen < 2.5) {
            return 'poor';
        }

        if ($garbledRatio > 0.05 || $avgWordLen < 3.5) {
            return 'fair';
        }

        return 'good';
    }

    // =========================================================================
    // LLM CALL
    // =========================================================================

    private function callLlm(string $text): ?string
    {
        $prompt = <<<PROMPT
You are a document data extraction AI. Extract structured data from the OCR text below.

STRICT RULES — you must follow every one:
- Return ONLY valid JSON. No markdown, no explanation, no preamble, no trailing text.
- Always close every JSON array and object. NEVER truncate the response.
- Aggressively fix OCR errors: correct misspelled words, fix garbled names, normalise dates to YYYY-MM-DD format.
- If a value is truly unreadable after correction attempts, use null. NEVER copy raw garbled OCR text as-is.
- Detect the document type automatically from the content.
- Extract ALL meaningful fields you can find.
- Use null for any field whose value cannot be determined.

Required output shape (add as many fields inside "fields" as needed):
{
  "document_type": "string",
  "data": {
    "fields": {}
  },
  "confidence": 0.0
}

DOCUMENT:
{$text}
PROMPT;

        try {
            $response = Http::timeout(300)->post(self::OLLAMA_URL, [
                'model'    => self::OLLAMA_MODEL,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream'  => false,
                'options' => [
                    'num_predict' => self::OLLAMA_MAX_TOKENS, // prevent truncated JSON
                    'temperature' => 0.1,                     // deterministic output
                ],
            ]);

            Log::info("Ollama status: " . $response->status());

            if (!$response->successful()) {
                Log::error("Ollama non-2xx response: " . $response->body());
                return null;
            }

            $json = $response->json();

            if (!isset($json['message']['content'])) {
                Log::error("Unexpected Ollama response structure: " . json_encode($json));
                return null;
            }

            return $json['message']['content'];

        } catch (Throwable $e) {
            Log::error("Ollama exception: " . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // JSON PARSER
    // =========================================================================

    private function parseJson(string $raw): ?array
    {
        // 1. Strip markdown fences
        $clean = preg_replace('/```(?:json)?\s*|```/', '', $raw);
        $clean = trim($clean);

        // 2. Fix trailing commas before ] or }  (common LLM mistake)
        $clean = $this->fixTrailingCommas($clean);

        // 3. Try direct decode on the whole string
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // 4. Extract the first complete { ... } block
        $start = strpos($clean, '{');
        $end   = strrpos($clean, '}');

        if ($start === false || $end === false) {
            Log::warning("JSON parse: no JSON object found — response may be truncated");
            return null;
        }

        $jsonString = substr($clean, $start, $end - $start + 1);
        $jsonString = $this->fixTrailingCommas($jsonString);

        $decoded = json_decode($jsonString, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // 5. Log the exact error to aid debugging
        Log::warning("JSON parse failed. Error: " . json_last_error_msg());
        Log::debug("JSON attempted snippet: " . substr($jsonString, 0, 500));

        return null;
    }

    /**
     * Remove trailing commas before closing ] or } — these are invalid in JSON
     * but extremely common in LLM output.
     *
     * e.g.  [{"a": 1},]   →  [{"a": 1}]
     */
    private function fixTrailingCommas(string $json): string
    {
        return preg_replace('/,\s*([\]\}])/', '$1', $json);
    }

    // =========================================================================
    // FAIL HELPER
    // =========================================================================

    /**
     * Mark the document as failed, log the reason, and fail the job immediately
     * (skipping remaining retry attempts for unrecoverable errors like missing files).
     */
    private function failJob(string $msg): void
    {
        Log::error("ProcessDocument failJob: {$msg}");

        $this->document->update(['status' => 'failed']);

        $this->fail(new \RuntimeException($msg));
    }
}