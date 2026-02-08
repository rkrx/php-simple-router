<?php

namespace Kir\Http\Routing\Common\OpenAPI;

use OpenApi\Attributes as OA;

class TestProductHandler {
	#[OA\Get(
		path: '/products/gtin-by-article-number/{articleNumber}',
		operationId: 'getGtinByArticleNumber',
		description: 'Returns the GTIN (GTIN-13) for a given article number.',
		summary: 'Resolve GTIN by article number',
		tags: ['Products'],
		parameters: [
			new OA\Parameter(
				name: 'articleNumber',
				description: 'Article number to resolve.',
				in: 'path',
				required: true,
				schema: new OA\Schema(type: 'string', minLength: 1, example: 'A-12345')
			),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: 'GTIN found.',
				content: new OA\MediaType(
					mediaType: 'text/plain',
					schema: new OA\Schema(type: 'string', example: '0123456789123')
				)
			),
			new OA\Response(
				response: 404,
				description: 'GTIN not found.',
				content: new OA\JsonContent(
					required: ['message'],
					properties: [
						new OA\Property(property: 'message', type: 'string', example: 'Not found'),
					],
					type: 'object'
				)
			),
		]
	)]
	public function getGtinByArticleNumber(string $articleNumber): string {
		return '0123456789123';
	}
}
