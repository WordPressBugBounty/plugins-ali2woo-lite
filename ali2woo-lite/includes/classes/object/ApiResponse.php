<?php

/**
 * Description of ExternalOrder
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ApiResponse
{

    public const STATE_OK = 'ok';
    public const STATE_ERROR = 'error';

    public const FIELD_MESSAGE = 'message';
    public const FIELD_STATE = 'state';
    public const FIELD_DATA = 'data';


    private string $state;
    private ?string $message;
    private ?array $data;


    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function isStateOk(): bool
    {
        return $this->state === self::STATE_OK;
    }

    public function setStateOk(): self
    {
        $this->state = self::STATE_OK;

        return $this;
    }

    public function setStateError(string $message): self
    {
        $this->state = self::STATE_ERROR;
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function toArray(): array
    {
        return [
            self::FIELD_MESSAGE => $this->message,
            self::FIELD_STATE => $this->state,
            self::FIELD_DATA => $this->data,
        ];
    }
}
