<?php

namespace Tests;

use DB;
use TestCase;
use Illuminate\Http\JsonResponse;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @package \Tests
 */
class ItemsTest extends TestCase
{
	/**
	 * @const TEST_TOKEN
	 * @see tests/TestCase.php
	 */

	use Utils\DataRules;

	/**
	 * @dataProvider \Tests\ChecklistsTest::checklistsToBeCreated
	 * @param array $checklist
	 * @return void
	 */
	public function testListAllItemsInGivenChecklists(array $checklist): void
	{
		static $id = 1;

		$this->json('GET', sprintf('/checklists/%d/items', $id), [], ['Authorization' => TEST_TOKEN]);

		// // Debug here
		// dd($this->response);

		// Make sure that the http response code is 201
		$this->assertEquals($this->response->status(), 201);

		// Make sure that the response is a JSON.
		$this->assertTrue($this->response instanceof JsonResponse);
		$json = $this->response->original;

		$rules = [
			"data" => "array",
			"data.type" => "string",
			"data.id" => "numeric",
			"data.attributes" => "array",
			"data.attributes.object_domain" => "string",
			"data.attributes.object_id" => "numeric",
			"data.attributes.description" => "string",
			"data.attributes.is_completed" => "boolean",
			"data.attributes.due" => "string",
			"data.attributes.urgency" => "numeric",
			"data.attributes.completed_at" => "NULL",
			"data.attributes.last_update_by" => "NULL",
			"data.attributes.created_at" => "string",
			"data.attributes.updated_at" => "string",
			"data.attributes.items" => "array",
			"data.links" => "array",
			"data.links.self" => ["string", function (string $value) {
				return filter_var($value, FILTER_VALIDATE_URL);
			}]
		];

		$this->assertTrue($this->assertRules($json, $rules));



		// With filter here...

		$id++;
	}
}
