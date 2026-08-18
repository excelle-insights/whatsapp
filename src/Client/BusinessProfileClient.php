<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Client;

class BusinessProfileClient extends BaseClient
{
    public function getProfile(string $phoneNumberId): object
    {
        return $this->sendRequest(
            'GET',
            "{$phoneNumberId}/whatsapp_business_profile"
        );
    }

    public function updateProfile(string $phoneNumberId, array $data): object
    {
        $payload = array_filter([
            'messaging_product' => 'whatsapp',
            'about'             => $data['about'] ?? null,
            'address'           => $data['address'] ?? null,
            'description'       => $data['description'] ?? null,
            'email'             => $data['email'] ?? null,
            'vertical'          => $data['vertical'] ?? null,
            'websites'          => !empty($data['websites']) ? $data['websites'] : null,
        ], fn($v) => $v !== null);

        return $this->sendRequest(
            'POST',
            "{$phoneNumberId}/whatsapp_business_profile",
            $payload
        );
    }

    public function getPhoneNumbers(string $wabaId): object
    {
        return $this->sendRequest(
            'GET',
            "{$wabaId}/phone_numbers"
        );
    }

    public function getPhoneNumber(string $phoneNumberId): object
    {
        return $this->sendRequest(
            'GET',
            "{$phoneNumberId}"
        );
    }

    public function registerPhoneNumber(string $phoneNumberId, string $pin): object
    {
        return $this->sendRequest(
            'POST',
            "{$phoneNumberId}/register",
            [
                'messaging_product' => 'whatsapp',
                'pin'               => $pin,
            ]
        );
    }

    public function getWabaInfo(string $wabaId): object
    {
        return $this->sendRequest(
            'GET',
            "{$wabaId}"
        );
    }

    public function resolveWabaFromPhone(string $phoneNumberId): object
    {
        return $this->sendRequest(
            'GET',
            "{$phoneNumberId}?fields=id,verified_name,display_phone_number,whatsapp_business_account{id,name}"
        );
    }

    public function listWabas(): object
    {
        $businesses = $this->sendRequest('GET', 'me/businesses?fields=id,name');
        $allWabas = ['data' => []];

        foreach ($businesses->data ?? [] as $business) {
            try {
                $result = $this->sendRequest(
                    'GET',
                    "{$business->id}/owned_whatsapp_business_accounts?fields=id,name,currency,timezone_id,message_template_namespace"
                );
                if (!empty($result->data)) {
                    array_push($allWabas['data'], ...$result->data);
                }
            } catch (\Throwable $e) {
                error_log("Skipping business {$business->id}: " . $e->getMessage());
            }
        }

        return (object) $allWabas;
    }

    public function verifyWaba(string $wabaId): object
    {
        return $this->sendRequest(
            'GET',
            "{$wabaId}"
        );
    }
}
