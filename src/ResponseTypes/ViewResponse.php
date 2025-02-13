<?php

namespace DvTeam\Routing\ResponseTypes;

use Override;

class ViewResponse extends AbstractHttpResponse {
	/**
	 * @param string $templateName
	 * @param array<string, mixed> $args
	 * @param int $statusCode
	 */
	public function __construct(
		private readonly string $templateName,
		private readonly array $args = [],
		int $statusCode = 200
	) {
		parent::__construct($statusCode);
	}

	public function getTemplateName(): string {
		return $this->templateName;
	}
	
	/**
	 * @return array<string, mixed>
	 */
	public function getParams(): array {
		return $this->args;
	}

	#[Override]
    public function jsonSerialize(): mixed {
		$data = parent::jsonSerialize();
		$data['template'] = $this->templateName;
		$data['args'] = $this->args;
		return $data;
	}
}
