<?php

namespace HuntRecipes\Endpoint;

use HuntRecipes\Exception\HuntRecipesException;

class FileUploadController {
    private $file;
    private $file_key;

    // List of allowed MIME types
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ];

    private const ALLOWED_AUDIO_TYPES = [
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/mp4',
        'audio/aac'
    ];

    public function __construct(string $file_key) {
        $this->file_key = $file_key;

        if (!isset($_FILES[$file_key])) {
            throw new HuntRecipesException("No file uploaded with key: {$file_key}");
        }

        $this->file = $_FILES[$file_key];
    }

    public function is_valid(): bool {
        return $this->file['error'] === UPLOAD_ERR_OK;
    }

    public function get_error(): string {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive: ' . ini_get('upload_max_filesize'),
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
        ];

        return $errors[$this->file['error']] ?? 'Unknown upload error';
    }

    public function get_name(): string {
        return $this->file['name'];
    }

    public function get_size(): int {
        return $this->file['size'];
    }

    public function get_mime_type(): string {
        return $this->file['type'];
    }

    public function get_temp_path(): string {
        return $this->file['tmp_name'];
    }

    public function is_image(): bool {
        return in_array($this->get_mime_type(), self::ALLOWED_IMAGE_TYPES);
    }

    public function is_audio(): bool {
        return in_array($this->get_mime_type(), self::ALLOWED_AUDIO_TYPES);
    }

    public function move(string $relative_dir): string {
        if (!$this->is_valid()) {
            return false;
        }

        if (str_ends_with($relative_dir, "/")) {
            $relative_dir = rtrim($relative_dir, "/");
        }

        if (!is_dir(RECIPES_ROOT . "/$relative_dir")) {
            throw new HuntRecipesException("Destination directory does not exist: {$relative_dir}");
        }

        $new_file = "$relative_dir/" . $this->get_name();
        if (file_exists($new_file)) {
            $i = 1;

            // If file exists, add number to its name.
            while (file_exists(RECIPES_ROOT . "/$new_file")) {
                $new_file = "$relative_dir/" . pathinfo($new_file, PATHINFO_FILENAME)
                    . "-" . $i
                    . "." . pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

                $i++;
            }
        }

        move_uploaded_file($this->get_temp_path(), RECIPES_ROOT . "/$new_file");
        return $new_file;
    }

    public function validate(array $options = []): array {
        $errors = [];

        // Check if file was successfully uploaded
        if (!$this->is_valid()) {
            $errors[] = $this->get_error();
            return $errors;
        }

        // Validate file size
        if (isset($options['max_size'])) {
            if ($this->get_size() > $options['max_size']) {
                $errors[] = 'File size exceeds maximum allowed size';
            }
        }

        // Validate file type
        if (isset($options['allowed_types'])) {
            if (!in_array($this->get_mime_type(), $options['allowed_types'])) {
                $errors[] = 'File type not allowed';
            }
        }

        return $errors;
    }
}
