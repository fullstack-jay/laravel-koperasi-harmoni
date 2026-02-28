<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Resources;

use AllowDynamicProperties;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="ActivityLogResource",
 *     title="Activity Log Resource",
 *     description="Activity log resource representation",
 *     type="object",
 *
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="description", type="string", example="User logged in successfully"),
 *     @OA\Property(property="event", type="string", example="login"),
 *     @OA\Property(property="severity", type="string", enum={"info", "warning", "error", "success"}, example="success"),
 *     @OA\Property(property="severityColor", type="string", example="#10B981"),
 *     @OA\Property(property="severityIcon", type="string", example="✅"),
 *     @OA\Property(property="severityLabel", type="string", example="Success"),
 *     @OA\Property(property="causer", type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="firstName", type="string", example="John"),
 *         @OA\Property(property="lastName", type="string", example="Doe"),
 *         @OA\Property(property="fullName", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", example="john@example.com"),
 *     ),
 *     @OA\Property(property="subject", type="object", nullable=true),
 *     @OA\Property(property="properties", type="object", example={"ip_address": "127.0.0.1", "user_agent": "Mozilla"}),
 *     @OA\Property(property="changes", type="object", nullable=true, example={"old": {"name": "John"}, "new": {"name": "Jane"}}),
 *     @OA\Property(property="createdAt", type="string", example="2025-01-15 10:30:00"),
 * )
 */
#[AllowDynamicProperties] final class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle created_at - can be timestamp string or DateTime object
        $createdAt = null;
        if ($this->created_at) {
            if (is_numeric($this->created_at)) {
                $createdAt = date('Y-m-d H:i:s', (int) $this->created_at);
            } else {
                $createdAt = $this->created_at;
            }
        }

        // Get causer info
        $causer = $this->whenLoaded('user') ? $this->user : null;
        $causerData = null;
        if ($causer) {
            $causerData = [
                'id' => $causer->id,
                'firstName' => $causer->first_name ?? null,
                'lastName' => $causer->last_name ?? null,
                'fullName' => $causer->full_name ?? trim(($causer->first_name ?? '') . ' ' . ($causer->last_name ?? '')),
                'email' => $causer->email ?? null,
                'username' => $causer->username ?? null,
            ];
        }

        // Get subject info
        $subject = $this->whenLoaded('subject') ? $this->subject : null;

        // Get changes
        $changes = null;
        if ($this->old_values && $this->new_values) {
            $changes = [
                'old' => $this->old_values,
                'new' => $this->new_values,
            ];
        }

        return [
            'id' => $this->id,
            'logName' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
            'severity' => $this->severity ?? 'info',
            'severityColor' => $this->severity_color,
            'severityIcon' => $this->severity_icon,
            'severityLabel' => $this->severity_label,
            'causer' => $causerData,
            'subjectType' => $this->subject_type,
            'subjectId' => $this->subject_id,
            'subject' => $subject,
            'properties' => $this->properties ?? [],
            'changes' => $changes,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'sessionId' => $this->session_id,
            'requestId' => $this->request_id,
            'batchUuid' => $this->batch_uuid,
            'createdAt' => $createdAt,
        ];
    }
}
