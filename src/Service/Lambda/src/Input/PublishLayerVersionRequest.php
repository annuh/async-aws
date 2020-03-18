<?php

namespace AsyncAws\Lambda\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\Lambda\Enum\Runtime;
use AsyncAws\Lambda\ValueObject\LayerVersionContentInput;

class PublishLayerVersionRequest
{
    /**
     * The name or Amazon Resource Name (ARN) of the layer.
     *
     * @required
     *
     * @var string|null
     */
    private $LayerName;

    /**
     * The description of the version.
     *
     * @var string|null
     */
    private $Description;

    /**
     * The function layer archive.
     *
     * @required
     *
     * @var LayerVersionContentInput|null
     */
    private $Content;

    /**
     * A list of compatible function runtimes. Used for filtering with ListLayers and ListLayerVersions.
     *
     * @see https://docs.aws.amazon.com/lambda/latest/dg/lambda-runtimes.html
     *
     * @var list<Runtime::*>
     */
    private $CompatibleRuntimes;

    /**
     * The layer's software license. It can be any of the following:.
     *
     * @var string|null
     */
    private $LicenseInfo;

    /**
     * @param array{
     *   LayerName?: string,
     *   Description?: string,
     *   Content?: \AsyncAws\Lambda\ValueObject\LayerVersionContentInput|array,
     *   CompatibleRuntimes?: list<\AsyncAws\Lambda\Enum\Runtime::*>,
     *   LicenseInfo?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->LayerName = $input['LayerName'] ?? null;
        $this->Description = $input['Description'] ?? null;
        $this->Content = isset($input['Content']) ? LayerVersionContentInput::create($input['Content']) : null;
        $this->CompatibleRuntimes = $input['CompatibleRuntimes'] ?? [];
        $this->LicenseInfo = $input['LicenseInfo'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return list<Runtime::*>
     */
    public function getCompatibleRuntimes(): array
    {
        return $this->CompatibleRuntimes;
    }

    public function getContent(): ?LayerVersionContentInput
    {
        return $this->Content;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function getLayerName(): ?string
    {
        return $this->LayerName;
    }

    public function getLicenseInfo(): ?string
    {
        return $this->LicenseInfo;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/json'];

        // Prepare query
        $query = [];

        // Prepare URI
        $uri = [];
        $uri['LayerName'] = $this->LayerName ?? '';
        $uriString = "/2018-10-31/layers/{$uri['LayerName']}/versions";

        // Prepare Body
        $bodyPayload = $this->requestBody();
        $body = empty($bodyPayload) ? '{}' : json_encode($bodyPayload);

        // Return the Request
        return new Request('POST', $uriString, $query, $headers, StreamFactory::create($body));
    }

    /**
     * @param list<Runtime::*> $value
     */
    public function setCompatibleRuntimes(array $value): self
    {
        $this->CompatibleRuntimes = $value;

        return $this;
    }

    public function setContent(?LayerVersionContentInput $value): self
    {
        $this->Content = $value;

        return $this;
    }

    public function setDescription(?string $value): self
    {
        $this->Description = $value;

        return $this;
    }

    public function setLayerName(?string $value): self
    {
        $this->LayerName = $value;

        return $this;
    }

    public function setLicenseInfo(?string $value): self
    {
        $this->LicenseInfo = $value;

        return $this;
    }

    public function validate(): void
    {
        if (null === $this->LayerName) {
            throw new InvalidArgument(sprintf('Missing parameter "LayerName" when validating the "%s". The value cannot be null.', __CLASS__));
        }

        if (null === $this->Content) {
            throw new InvalidArgument(sprintf('Missing parameter "Content" when validating the "%s". The value cannot be null.', __CLASS__));
        }
        $this->Content->validate();

        foreach ($this->CompatibleRuntimes as $item) {
            if (!Runtime::exists($item)) {
                throw new InvalidArgument(sprintf('Invalid parameter "CompatibleRuntimes" when validating the "%s". The value "%s" is not a valid "Runtime".', __CLASS__, $item));
            }
        }
    }

    /**
     * @internal
     */
    private function requestBody(): array
    {
        $payload = [];

        if (null !== $v = $this->Description) {
            $payload['Description'] = $v;
        }
        if (null !== $v = $this->Content) {
            $payload['Content'] = $v->requestBody();
        }

        $index = -1;
        foreach ($this->CompatibleRuntimes as $mapValue) {
            ++$index;
            $payload['CompatibleRuntimes'][$index] = $mapValue;
        }

        if (null !== $v = $this->LicenseInfo) {
            $payload['LicenseInfo'] = $v;
        }

        return $payload;
    }
}