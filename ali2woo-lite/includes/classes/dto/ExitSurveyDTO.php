<?php

namespace AliNext_Lite;;

class ExitSurveyDTO
{
    public string $reason;
    public string $other;
    public ?string $contactEmail;

    public function __construct(string $reason, string $other, ?string $contactEmail = null)
    {
        $this->reason = $reason;
        $this->other = $other;
        $this->contactEmail = $contactEmail;
    }

    public static function build(
        string $reason,
        string $other,
        ?string $contactEmail = null
    ): self {
        return new self($reason, $other, $contactEmail);
    }

    public function toArray(): array
    {
        return [
            'reason' => $this->reason,
            'other' => $this->other,
            'contact_email' => $this->contactEmail,
        ];
    }
}
