<?php
declare(strict_types=1);

namespace ExcelleInsights\WhatsApp\Services;

use ExcelleInsights\WhatsApp\Client\BusinessProfileClient;
use ExcelleInsights\WhatsApp\Repositories\BusinessProfileRepository;

class BusinessProfileService
{
    public function __construct(
        private BusinessProfileRepository $profiles,
        private BusinessProfileClient $client,
    ) {}

    public function link(string $wabaOrPhoneId, ?string $phoneNumberIdParam = null): object
    {
        try {
            // Attempt to resolve WABA from the given ID
            // The ID might be a phone number ID or a WABA ID
            $resolveResult = $this->resolveId($wabaOrPhoneId, $phoneNumberIdParam);

            $wabaId = $resolveResult->waba_id;
            $phoneNumberId = $resolveResult->phone_number_id;
            $phoneNumber = $resolveResult->phone_number;
            $wabaInfo = $resolveResult->waba_info;

            if (!$phoneNumber && $phoneNumberId) {
                try {
                    $phoneInfo = $this->client->getPhoneNumber($phoneNumberId);
                    $phoneNumber = $phoneInfo->display_phone_number ?? null;
                } catch (\Throwable $e) {
                    error_log("Could not fetch phone number info: " . $e->getMessage());
                }
            }

            // Try to fetch business profile (may fail due to permissions)
            $businessProfile = null;
            if (!$phoneNumberId) {
                try {
                    $phones = $this->client->getPhoneNumbers($wabaId);
                    if (!empty($phones->data[0]->id)) {
                        $phoneNumberId = $phones->data[0]->id;
                    }
                } catch (\Throwable $e) {
                    error_log("Could not fetch phone numbers for business profile: " . $e->getMessage());
                }
            }
            if ($phoneNumberId) {
                try {
                    $businessProfile = $this->client->getProfile($phoneNumberId);
                } catch (\Throwable $e) {
                    error_log("Could not fetch business profile: " . $e->getMessage());
                }
            }

            // Upsert: update existing or create new
            $existing = $this->profiles->findByWabaId($wabaId);

            if ($existing) {
                $this->profiles->update($existing->id, [
                    'name'             => $this->profileName($wabaInfo, $existing->name),
                    'phone_number_id'  => $phoneNumberId ?? $existing->phone_number_id,
                    'phone_number'     => $phoneNumber ?? $existing->phone_number,
                    'status'           => 'active',
                ]);
                $profile = $this->profiles->find($existing->id);
                $source = 'updated';

                if ($existing->waba_id !== $wabaId) {
                    $this->profiles->update($existing->id, ['waba_id' => $wabaId]);
                    $profile = $this->profiles->find($existing->id);
                }
            } else {
                $localId = $this->profiles->create([
                    'name'             => $this->profileName($wabaInfo, $wabaId),
                    'waba_id'          => $wabaId,
                    'business_id'      => null,
                    'phone_number_id'  => $phoneNumberId,
                    'phone_number'     => $phoneNumber,
                    'currency'         => null,
                    'timezone_id'      => null,
                    'status'           => 'active',
                ]);
                $profile = $this->profiles->find($localId);
                $source = 'created';
            }

            return (object)[
                'status'   => 'linked',
                'profile'  => $profile,
                'source'   => $source,
                'waba_id'  => $wabaId,
                'data'     => [
                    'waba_info'         => $wabaInfo,
                    'business_profile'  => $businessProfile,
                ],
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }

    private function resolveId(string $id, ?string $phoneNumberIdParam): object
    {
        $info = $this->client->getWabaInfo($id);

        $isPhoneNumber = isset($info->display_phone_number);

        if ($isPhoneNumber) {
            try {
                $resolved = $this->client->resolveWabaFromPhone($id);
                if (isset($resolved->whatsapp_business_account)) {
                    $waba = $resolved->whatsapp_business_account;
                    return (object)[
                        'waba_id'          => $waba->id,
                        'phone_number_id'  => $resolved->id ?? $id,
                        'phone_number'     => $resolved->display_phone_number ?? null,
                        'waba_info'        => $resolved,
                    ];
                }
            } catch (\Throwable $e) {
                error_log("Could not resolve WABA from phone: " . $e->getMessage());
            }

            throw new \RuntimeException(
                "ID '{$id}' appears to be a phone number (display_phone_number: "
                . ($info->display_phone_number ?? 'unknown') . "), but couldn't resolve "
                . "the WABA ID. Provide your WABA ID directly (found in Business Manager "
                . "> WhatsApp Accounts)."
            );
        }

        return (object)[
            'waba_id'          => $id,
            'phone_number_id'  => $phoneNumberIdParam,
            'phone_number'     => null,
            'waba_info'        => $info,
        ];
    }

    private function profileName(object $wabaInfo, string $fallback = 'WhatsApp Business'): string
    {
        return $wabaInfo->name
            ?? $wabaInfo->verified_name
            ?? $wabaInfo->display_phone_number
            ?? $fallback;
    }

    public function getProfile(int $profileId): ?object
    {
        return $this->profiles->find($profileId);
    }

    public function getBusinessProfileData(int $profileId): object
    {
        $profile = $this->profiles->find($profileId);

        if (!$profile) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found',
            ];
        }

        try {
            $data = $this->client->getProfile($profile->phone_number_id);

            return (object)[
                'status'  => 'success',
                'profile' => $profile,
                'data'    => $data,
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
                'profile' => $profile,
            ];
        }
    }

    public function updateBusinessProfile(int $profileId, array $data): object
    {
        $profile = $this->profiles->find($profileId);

        if (!$profile) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found',
            ];
        }

        $allowedFields = ['about', 'address', 'description', 'email', 'vertical', 'websites'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return (object)[
                'status' => 'failed',
                'error'  => 'No valid fields provided',
            ];
        }

        try {
            $this->client->updateProfile($profile->phone_number_id, $updateData);

            return (object)[
                'status' => 'success',
                'message' => 'Business profile updated',
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }

    public function discoverWabas(): object
    {
        try {
            $result = $this->client->listWabas();

            $wabas = $result->data ?? [];

            return (object)[
                'status' => 'success',
                'data'   => $wabas,
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }

    public function syncPhoneNumbers(int $profileId): object
    {
        $profile = $this->profiles->find($profileId);

        if (!$profile) {
            return (object)[
                'status' => 'failed',
                'error'  => 'Business profile not found',
            ];
        }

        try {
            $phoneNumbers = $this->client->getPhoneNumbers($profile->waba_id);

            if (isset($phoneNumbers->data) && count($phoneNumbers->data) > 0) {
                $firstPhone = $phoneNumbers->data[0];
                $this->profiles->update($profileId, [
                    'phone_number_id' => $firstPhone->id ?? $profile->phone_number_id,
                    'phone_number'    => $firstPhone->display_phone_number ?? $profile->phone_number,
                ]);
            }

            return (object)[
                'status' => 'success',
                'data'   => $phoneNumbers,
            ];
        } catch (\Throwable $e) {
            return (object)[
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }
}
