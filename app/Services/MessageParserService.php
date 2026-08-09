<?php

namespace App\Services;

/**
 * Parses a raw Telegram message array into normalized fields.
 */
class MessageParserService
{
    /**
     * Parse a raw MadelineProto message into structured fields.
     *
     * @param array $rawMessage  MadelineProto message object
     * @return array|null  Returns null if the message has no parseable content
     */
    public function parse(array $rawMessage): ?array
    {
        $type = $rawMessage['_'] ?? '';
        if ($type !== 'message') {
            return null; // skip messageService, messageEmpty, etc.
        }

        $messageId  = $rawMessage['id'] ?? null;
        $text       = $rawMessage['message'] ?? '';
        $date       = $rawMessage['date'] ?? null;
        $postedAt   = $date ? date('Y-m-d H:i:s', $date) : now()->toDateTimeString();

        // Only process messages that have media OR text
        $hasMedia = isset($rawMessage['media']) && ($rawMessage['media']['_'] ?? '') !== 'messageMediaEmpty';
        $hasText  = !empty(trim($text));

        if (!$hasMedia && !$hasText) {
            return null;
        }

        $price      = $this->extractPrice($text);
        $productKey = $this->buildProductKey($text);

        // If we can't form a meaningful product key, skip
        if (empty($productKey)) {
            return null;
        }

        return [
            'product_key'  => $productKey,
            'product_name' => $this->buildProductName($text),
            'price'        => $price,
            'message_id'   => $messageId,
            'message_text' => $text,
            'posted_at'    => $postedAt,
            'has_media'    => $hasMedia,
            'raw_message'  => $rawMessage,  // kept for photo download
        ];
    }

    /**
     * Get all price-matching regex patterns.
     */
    private function getPricePatterns(): array
    {
        return [
            // ₹1,500  $1500.00  €1.500
            '/[\$₹€£¥₪₺₩฿]\s*([\d]{1,3}(?:[,\.][\d]{3})*(?:[,\.][\d]{1,2})?|\d+)/u',
            // Rs 1500 / Rs. 1500 / INR 1500
            '/\b(?:rs\.?|rupees?|inr|dhs?\.?|dirhams?)\s*([\d]{1,3}(?:[,\.][\d]{3})*(?:[,\.][\d]{1,2})?|\d+)/i',
            // 1500 ₹ / 1500 $
            '/([\d]{1,3}(?:[,\.][\d]{3})*(?:[,\.][\d]{1,2})?|\d+)\s*[\$₹€£¥₪₺₩฿]/u',
            // 1500/- or 1500 /- (Indian Rupee shorthand)
            '/([\d]{1,3}(?:[,\.][\d]{3})*(?:[,\.][\d]{1,2})?|\d+)\s*\/\-/u',
            // 1500 USD / 1,500.00 AED / 1500 INR
            '/([\d]{1,3}(?:[,\.][\d]{3})*(?:[,\.][\d]{1,2})?|\d+)\s*(?:USD|INR|AED|SAR|EUR|GBP|PKR|BDT|LKR|NPR|QAR|KWD|BHD|OMR|EGP)\b/i',
            // 1500 rupees / 1500 dollars / 1500 rs
            '/([\d]{1,3}(?:[,\.][\d]{3})*(?:[,\.][\d]{1,2})?|\d+)\s*(?:rupees?|dollars?|rs\.?|dhs?\.?|dirhams?)\b/i',
            // Price: 1500 / Rate: 1500 / Cost: 1500 (Removed generic words like 'total' or 'amount' to avoid qty mismatches)
            '/(?:price|rate|cost|mrp|sp|selling\s*price)[\s:=]+([₹$€£¥]?\s*[\d]{1,3}(?:[,\.][\d]{3})*(?:[,\.][\d]{1,2})?|\d+)/i',
        ];
    }

    /**
     * Extract the first recognizable price from a text string.
     * Returns null if none found.
     */
    public function extractPrice(string $text): ?float
    {

        foreach ($this->getPricePatterns() as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // $matches[1] has the number part
                $raw = $matches[1] ?? $matches[0];
                // Normalize: remove all commas, keep last period as decimal
                $normalized = $this->normalizeNumber($raw);
                if ($normalized > 0) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    /**
     * Build a stable product key from message text.
     * Lowercase, strip emoji/punctuation, collapse whitespace, take first 250 chars.
     */
    public function buildProductKey(string $text): string
    {
        $name = $this->buildProductName($text);
        
        // Remove emoji (Unicode ranges for emoji)
        $clean = preg_replace('/[\x{1F000}-\x{1FFFF}]/u', '', $name ?? '');
        $clean = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $clean ?? '');
        $clean = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $clean ?? '');

        // Lowercase
        $clean = mb_strtolower($clean ?? '');

        // Remove all punctuation (keep alphanumeric + spaces)
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean ?? '');

        // Collapse whitespace
        $clean = preg_replace('/\s+/', ' ', $clean ?? '');
        $clean = trim($clean ?? '');

        // Limit length for DB unique index
        return mb_substr($clean, 0, 250);
    }

    /**
     * Build a human-readable product name (first meaningful line of text).
     */
    public function buildProductName(string $text): string
    {
        $lines = preg_split('/[\r\n]+/', trim($text));
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip generic identifier/quantity lines
            if (preg_match('/^(?:code|item code|sku)?\s*[:=\-]?\s*\d+$/i', $line)) continue;
            if (preg_match('/^\d+\s*(?:ctn|pcs|piece|kg|g|ml|l|box|carton)/i', $line)) continue;

            if (!empty($line) && mb_strlen($line) > 2) {
                // Strip trailing price from the product name if present
                foreach ($this->getPricePatterns() as $pattern) {
                    // Extract existing modifiers
                    preg_match('/\/([a-z]*)$/i', $pattern, $modMatches);
                    $modifiers = $modMatches[1] ?? 'i';
                    if (!str_contains($modifiers, 'u')) $modifiers .= 'u';
                    if (!str_contains($modifiers, 'i')) $modifiers .= 'i';

                    $endPattern = preg_replace('/\/[a-z]*$/i', '(?:\s*\-)?\s*$/' . $modifiers, $pattern);
                    $line = preg_replace($endPattern, '', $line);
                }
                
                $line = preg_replace('/\s+[\-\*\#\~\:\;]+\s*$/', '', trim($line));
                $line = rtrim($line, " \t\n\r\0\x0B-*#~:;");
                
                return mb_substr($line, 0, 200);
            }
        }
        return mb_substr($text, 0, 200);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function normalizeNumber(string $raw): float
    {
        // Remove currency symbols
        $raw = preg_replace('/[^\d,\.]/u', '', $raw);

        // If the number has both commas and periods, figure out which is the decimal sep
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            // e.g. 1,500.00 (EN format) or 1.500,00 (EU format)
            $lastComma  = strrpos($raw, ',');
            $lastPeriod = strrpos($raw, '.');
            if ($lastComma > $lastPeriod) {
                // EU format: 1.500,00 → decimal is comma
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                // EN format: 1,500.00 → decimal is period
                $raw = str_replace(',', '', $raw);
            }
        } elseif (str_contains($raw, ',')) {
            // Could be 1,500 (thousands) or 1,50 (decimal)
            $parts = explode(',', $raw);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                // Likely decimal: 1,50 → 1.50
                $raw = implode('.', $parts);
            } else {
                // Thousands separator: 1,500 → 1500
                $raw = str_replace(',', '', $raw);
            }
        }

        return (float) $raw;
    }
}
