<?php
if (!defined('APP_ROOT')) die('Direct access not permitted');

/** Display-only allowlist. Never persist source payloads or citizen identifiers. */
class SdlStudentProfile
{
    public static function fields(array $payload): array
    {
        $personal = $payload['personal'] ?? [];
        if (!is_array($personal)) return [];
        $demographics = is_array($payload['demographics'] ?? null) ? $payload['demographics'] : [];
        $contact = is_array($payload['contact'] ?? null) ? $payload['contact'] : [];
        $profile = is_array($payload['profile'] ?? null) ? $payload['profile'] : [];
        $details = is_array($profile['personal'] ?? null) ? $profile['personal'] : [];
        $personal = array_replace($personal, [
            'birthdate_display' => $demographics['birth_date'] ?? $personal['birthdate_display'] ?? null,
            'gender' => $demographics['gender'] ?? $personal['gender'] ?? null,
            'occupation' => $details['occupation_label'] ?? $personal['occupation'] ?? null,
            'email' => $contact['email'] ?? $contact['email_masked'] ?? $personal['email'] ?? null,
            'phone' => $contact['phone'] ?? $personal['phone'] ?? null,
            'source_current_address' => $contact['current_address'] ?? null,
            'source_registered_address' => $contact['registered_address'] ?? null,
            'nationality' => $details['nationality_label'] ?? null,
            'religion' => $details['religion_label'] ?? null,
        ]);
        $fields = [];
        foreach (['birthdate_display', 'occupation', 'email', 'phone', 'source_current_address', 'source_registered_address', 'nationality', 'religion'] as $key) {
            $value = $personal[$key] ?? null;
            if (is_string($value) && trim($value) !== '') $fields[$key] = trim($value);
        }
        $gender = $personal['gender'] ?? null;
        $normalized = is_string($gender) ? match (strtolower(trim($gender))) {
            'male', 'm', '1', 'ชาย' => 'male',
            'female', 'f', '2', 'หญิง' => 'female',
            default => null,
        } : null;
        if ($normalized) $fields['gender'] = $normalized;
        return $fields;
    }

    public static function contacts(array $payload): array
    {
        $contacts = [];
        $rows = is_array($payload['guardians'] ?? null) ? $payload['guardians'] : [];
        $family = $payload['profile']['family'] ?? [];
        foreach (['father' => 'บิดา', 'mother' => 'มารดา', 'guardian' => 'ผู้ปกครอง'] as $key => $relationship) {
            $member = is_array($family) ? ($family[$key] ?? null) : null;
            if (is_array($member)) $rows[] = [
                'name' => $member['name'] ?? null,
                'relationship' => $relationship,
                'occupation' => $member['occupation_label'] ?? null,
            ];
        }
        $guardian = $payload['guardian'] ?? null;
        if (is_array($guardian)) $rows[] = array_replace($guardian, ['relationship' => $guardian['relationship'] ?? 'ผู้ปกครอง']);
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['name'] ?? null) || trim($row['name']) === '') continue;
            $contact = [];
            foreach (['name', 'relationship', 'phone', 'occupation'] as $key) {
                $contact[$key] = is_string($row[$key] ?? null) ? trim($row[$key]) : '';
            }
            $existing = array_search($contact['name'], array_column($contacts, 'name'), true);
            if ($existing === false) $contacts[] = $contact;
            elseif ($contact['phone'] !== '') $contacts[$existing]['phone'] = $contact['phone'];
        }
        return $contacts;
    }
}
