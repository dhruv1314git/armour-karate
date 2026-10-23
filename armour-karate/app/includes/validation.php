<?php
/**
 * Input Validation Helpers
 * app/includes/validation.php
 *
 * Server-side validation functions for all form inputs.
 * Equivalent to the Zod schemas used in the original Next.js project.
 *
 * Pattern:
 *   $v = new Validator($_POST);
 *   $v->required('name')->minLength('name', 2)->email('email');
 *   if ($v->fails()) { $errors = $v->errors(); }
 */

declare(strict_types=1);

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // ── Field access ──────────────────────────────────────────────

    public function get(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }

    public function getString(string $field): string
    {
        return trim((string) ($this->data[$field] ?? ''));
    }

    // ── Validation rules ──────────────────────────────────────────

    public function required(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value === '') {
            $this->errors[$field] = "{$label} is required.";
        }

        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value !== '' && mb_strlen($value) < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }

        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value !== '' && mb_strlen($value) > $max) {
            $this->errors[$field] = "{$label} must not exceed {$max} characters.";
        }

        return $this;
    }

    public function email(string $field, string $label = 'Email'): static
    {
        $value = $this->getString($field);

        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} is not a valid email address.";
        }

        return $this;
    }

    public function phone(string $field, string $label = 'Phone number'): static
    {
        $value = $this->getString($field);

        // Accepts: +91xxxxxxxxxx, 91xxxxxxxxxx, 10-digit, optional spaces/dashes
        if ($value !== '' && !preg_match('/^(\+?91[-\s]?)?[6-9]\d{9}$/', preg_replace('/[\s\-]/', '', $value))) {
            $this->errors[$field] = "{$label} is not a valid Indian mobile number.";
        }

        return $this;
    }

    public function date(string $field, string $label = 'Date'): static
    {
        $value = $this->getString($field);

        if ($value !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
            if ($dt === false || $dt->format('Y-m-d') !== $value) {
                $this->errors[$field] = "{$label} is not a valid date (use YYYY-MM-DD).";
            }
        }

        return $this;
    }

    public function inEnum(string $field, array $allowed, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "{$label} is not a valid option.";
        }

        return $this;
    }

    public function numeric(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "{$label} must be a number.";
        }

        return $this;
    }

    public function positiveNumber(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value !== '' && (float) $value <= 0) {
            $this->errors[$field] = "{$label} must be a positive number.";
        }

        return $this;
    }

    public function minValue(string $field, float $min, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value !== '' && is_numeric($value) && (float) $value < $min) {
            $this->errors[$field] = "{$label} must be at least {$min}.";
        }

        return $this;
    }

    public function passwordStrength(string $field, string $label = 'Password'): static
    {
        $value = $this->getString($field);

        if ($value !== '') {
            if (mb_strlen($value) < 8) {
                $this->errors[$field] = "{$label} must be at least 8 characters.";
            } elseif (!preg_match('/[A-Z]/', $value)) {
                $this->errors[$field] = "{$label} must contain at least one uppercase letter.";
            } elseif (!preg_match('/[0-9]/', $value)) {
                $this->errors[$field] = "{$label} must contain at least one number.";
            }
        }

        return $this;
    }

    public function matches(string $field, string $otherField, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $a = $this->getString($field);
        $b = $this->getString($otherField);

        if ($a !== '' && $b !== '' && $a !== $b) {
            $this->errors[$field] = "{$label} fields do not match.";
        }

        return $this;
    }

    public function url(string $field, string $label = 'URL'): static
    {
        $value = $this->getString($field);

        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "{$label} is not a valid URL.";
        }

        return $this;
    }

    public function integer(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->getString($field);

        if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = "{$label} must be a whole number.";
        }

        return $this;
    }

    // ── Custom rule ───────────────────────────────────────────────

    public function addError(string $field, string $message): static
    {
        $this->errors[$field] = $message;
        return $this;
    }

    // ── Results ───────────────────────────────────────────────────

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return reset($this->errors) ?: '';
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function getError(string $field): string
    {
        return $this->errors[$field] ?? '';
    }

    /**
     * Return all validated data (trimmed strings, original types).
     * Only returns fields that were in the input data.
     */
    public function validated(): array
    {
        $out = [];
        foreach ($this->data as $key => $value) {
            if ($key === '_csrf_token') continue; // never include CSRF token
            $out[$key] = is_string($value) ? trim($value) : $value;
        }
        return $out;
    }
}

// ─── Standalone helpers ───────────────────────────────────────────────────────

/**
 * Sanitize a string for safe database use.
 * Note: With PDO prepared statements, this is supplementary only.
 *
 * @param string $input
 * @return string
 */
function sanitizeString(string $input): string
{
    return trim(strip_tags($input));
}

/**
 * Validate a file upload array ($_FILES entry) for safe image storage.
 *
 * @param array  $file      Entry from $_FILES
 * @param int    $maxBytes  Maximum allowed file size in bytes
 * @param array  $allowed   Allowed MIME types
 * @return array{ok: bool, error: string}
 */
function validateImageUpload(array $file, int $maxBytes = 2 * 1024 * 1024, array $allowed = ['image/jpeg', 'image/png', 'image/webp']): array
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        ];
        $msg = $errors[$file['error']] ?? 'Unknown upload error.';
        return ['ok' => false, 'error' => $msg];
    }

    // Size check
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'File size must not exceed ' . round($maxBytes / 1024 / 1024, 1) . ' MB.'];
    }

    // Server-side MIME check using finfo (ignores filename extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed, true)) {
        return ['ok' => false, 'error' => 'Only JPEG, PNG, and WebP images are allowed.'];
    }

    return ['ok' => true, 'error' => ''];
}
