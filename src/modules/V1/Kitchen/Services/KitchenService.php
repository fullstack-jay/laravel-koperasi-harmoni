<?php

declare(strict_types=1);

namespace Modules\V1\Kitchen\Services;

use Exception;
use Modules\V1\Kitchen\DTOs\DapurData;
use Modules\V1\Kitchen\Models\Dapur;

class KitchenService
{
    /**
     * Get or create dapur from user request data
     * Used specifically in user creation/update flows
     *
     * @param array $requestData
     * @param string $defaultName
     * @param string $defaultPicName
     * @param string|null $existingDapurId
     * @return string|null Dapur ID or null if no dapur data
     * @throws Exception
     */
    public function getOrCreateForUser(
        array $requestData,
        string $defaultName,
        string $defaultPicName,
        ?string $existingDapurId = null
    ): ?string {
        // If existing dapur ID is provided and no new data, return existing
        if ($existingDapurId && !$this->hasDapurData($requestData)) {
            return $existingDapurId;
        }

        // If existing dapur and has new data, update it
        if ($existingDapurId && $this->hasDapurData($requestData)) {
            return $this->updateForUser($existingDapurId, $requestData, $defaultPicName);
        }

        // Create new dapur if has data
        if ($this->hasDapurData($requestData)) {
            return $this->createForUser($requestData, $defaultName, $defaultPicName);
        }

        return null;
    }

    /**
     * Check if request has dapur data
     */
    private function hasDapurData(array $data): bool
    {
        $dapurData = $data['dapur_data'] ?? [];

        return !empty($dapurData['name'])
            || !empty($dapurData['dapur_code'])
            || !empty($data['dapurNama']);
    }

    /**
     * Create new dapur from user request
     */
    private function createForUser(array $requestData, string $defaultName, string $defaultPicName): string
    {
        $dapurData = DapurData::fromRequest($requestData, $defaultName, $defaultPicName);

        $dapur = Dapur::create($dapurData->toArray());

        return $dapur->id;
    }

    /**
     * Update existing dapur from user request
     */
    private function updateForUser(string $dapurId, array $requestData, string $defaultPicName): string
    {
        $dapur = Dapur::find($dapurId);

        if (!$dapur) {
            throw new Exception('Dapur not found');
        }

        $dapurData = DapurData::fromRequest($requestData, $dapur->name, $defaultPicName);

        // Update fields if provided
        if ($dapurData->code) {
            $dapur->code = $dapurData->code;
        }
        if ($dapurData->name) {
            $dapur->name = $dapurData->name;
        }
        if ($dapurData->location) {
            $dapur->location = $dapurData->location;
        }
        if ($dapurData->picName) {
            $dapur->pic_name = $dapurData->picName;
        }
        if ($dapurData->picPhone) {
            $dapur->pic_phone = $dapurData->picPhone;
        }

        $dapur->save();

        return $dapur->id;
    }
}
