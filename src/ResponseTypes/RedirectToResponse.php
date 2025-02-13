<?php

namespace DvTeam\Routing\ResponseTypes;

use Override;

class RedirectToResponse extends AbstractHttpResponse {
	/**
	 * @param mixed[] $params
	 * @param string|null $fragment
	 * @param int $statusCode
	 */
	public function __construct(
		private readonly array $params,
		private readonly ?string $fragment = null,
		int $statusCode = 302
	) {
		parent::__construct($statusCode);
	}
	
	/**
	 * @return mixed[]
	 */
	public function getParams(): array {
		return $this->params;
	}

	public function getFragment(): ?string {
		return $this->fragment;
	}

	#[Override]
    public function jsonSerialize(): mixed {
		$data = parent::jsonSerialize();
		$data['params'] = $this->params;
		$data['fragment'] = $this->fragment;
		return $data;
	}
}
