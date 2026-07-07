<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Validation;

class TemplateValidator
{
    public static function validate(array $data): void
    {
        $required = ['name', 'language', 'profile_id', 'components'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("{$field} is required.");
            }
        }

        self::validateName($data['name']);
        self::validateLanguage($data['language']);
        self::validateComponents($data['components']);
    }

    public static function validateName(string $name): void
    {
        if (strlen($name) > 512) {
            throw new \InvalidArgumentException('Template name must not exceed 512 characters.');
        }

        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException(
                'Template name must be lowercase snake_case (letters, numbers, underscores only).'
            );
        }
    }

    public static function validateLanguage(string $language): void
    {
        $allowed = [
            'af', 'sq', 'ar', 'az', 'bn', 'bg', 'ca', 'zh_CN', 'zh_HK', 'zh_TW',
            'hr', 'cs', 'da', 'nl', 'en', 'en_GB', 'en_US', 'et', 'fil', 'fi',
            'fr', 'de', 'el', 'gu', 'ha', 'he', 'hi', 'hu', 'id', 'ga',
            'it', 'ja', 'kn', 'kk', 'rw_RW', 'ko', 'ky_KG', 'lo', 'lv', 'lt',
            'mk', 'ms', 'ml', 'mr', 'nb', 'fa', 'pl', 'pt_BR', 'pt_PT', 'pa',
            'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'es_AR', 'es_ES', 'es_MX', 'sw',
            'sv', 'ta', 'te', 'th', 'tr', 'uk', 'ur', 'uz', 'vi', 'zu',
        ];

        if (!in_array($language, $allowed)) {
            throw new \InvalidArgumentException(
                "Unsupported language code: {$language}"
            );
        }
    }

    public static function validateComponents(array $components): void
    {
        if (empty($components)) {
            throw new \InvalidArgumentException('At least one component is required.');
        }

        $hasBody = false;
        $allowedTypes = ['HEADER', 'BODY', 'FOOTER', 'BUTTONS'];

        foreach ($components as $index => $component) {
            $type = $component['type'] ?? '';

            if (!in_array($type, $allowedTypes)) {
                throw new \InvalidArgumentException(
                    "Invalid component type '{$type}' at index {$index}. Allowed: " . implode(', ', $allowedTypes)
                );
            }

            if ($type === 'BODY') {
                $hasBody = true;

                if (empty($component['text'])) {
                    throw new \InvalidArgumentException('BODY component must have text.');
                }
            }

            if ($type === 'HEADER') {
                $format = $component['format'] ?? 'TEXT';
                $allowedFormats = ['TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'];

                if (!in_array($format, $allowedFormats)) {
                    throw new \InvalidArgumentException(
                        "Invalid HEADER format '{$format}' at index {$index}."
                    );
                }

                if ($format === 'TEXT' && empty($component['text'])) {
                    throw new \InvalidArgumentException('TEXT header must have text content.');
                }
            }

            if ($type === 'BUTTONS') {
                $buttons = $component['buttons'] ?? [];

                if (empty($buttons)) {
                    throw new \InvalidArgumentException('BUTTONS component must have at least one button.');
                }

                if (count($buttons) > 3) {
                    throw new \InvalidArgumentException('Maximum 3 buttons allowed.');
                }

                foreach ($buttons as $btnIndex => $button) {
                    $btnType = $button['type'] ?? '';

                    if (!in_array($btnType, ['QUICK_REPLY', 'URL', 'PHONE_NUMBER', 'COPY_CODE', 'FLOW'])) {
                        throw new \InvalidArgumentException(
                            "Invalid button type '{$btnType}' at index {$btnIndex}."
                        );
                    }
                }
            }
        }

        if (!$hasBody) {
            throw new \InvalidArgumentException('A BODY component is required.');
        }
    }
}
