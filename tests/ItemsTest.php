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

		// Without filter.
		$this->json('GET', sprintf('/checklists/%d/items', $id), [], ['Authorization' => TEST_TOKEN]);

		// // Debug here
		// dd($this->response);

		// Make sure that the http response code is 200 OK
		$this->assertEquals($this->response->status(), 200);

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

	/**
	 * @depends testListAllItemsInGivenChecklists
	 * @dataProvider \Tests\ChecklistsTest::checklistsToBeCreated
	 * @param array $checklist
	 * @return void
	 */
	public function testGetChecklistItem(array $checklist): void
	{
		static $id = 1;

		foreach ($checklist["items"] as $k => $item) {
			// Without filter.
			$this->json('GET', sprintf('/checklists/%d/items/%d', $id, $k + 1), [], ['Authorization' => TEST_TOKEN]);

			// // Debug here
			// dd($this->response);

			// Make sure that the http response code is 200 OK
			$this->assertEquals($this->response->status(), 200);

			// Make sure that the response is a JSON.
			$this->assertTrue($this->response instanceof JsonResponse);
			$json = $this->response->original;

			// Ambiguous documetation
			// https://kw-checklist.docs.stoplight.io/api-reference/items/get-checklist-item-details
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
				"data.attributes.item" => "array",
				"data.attributes.item.id" => "numeric",
				"data.attributes.item.item_id" => "numeric",
				"data.attributes.item.checklist_id" => "numeric",
				"data.attributes.item.name" => "string",
				"data.attributes.item.due" => null,
				"data.attributes.item.urgency" => "numeric",
				"data.attributes.item.assignee_id" => "numeric",
				"data.attributes.item.task_id" => "numeric",
				"data.attributes.item.completed_at" => null,
				"data.attributes.item.last_update_by" => null,
				"data.attributes.item.created_at" => "string",
				"data.attributes.item.updated_at" => "string",
				"data.links" => "array",
				"data.links.self" => ["string", function (string $value) {
					return filter_var($value, FILTER_VALIDATE_URL);
				}]
			];

			$this->assertTrue($this->assertRules($json, $rules));
		}

		$id++;
	}

	/**
	 * @dataProvider itemsToBeCompleted
	 * @param array $items
	 * @return void
	 */
	public function testCompleteItem(array $items): void
	{
		$data = ["data" => []];

		foreach ($items["item_id"] as $item) {
			$data["data"][] = [
				"item_id" => $item
			];
		}

		$this->json('POST', sprintf('/checklists/%d/complete', $items["checklist_id"]),
			$data, ['Authorization' => TEST_TOKEN]);

		// // Debug here
		// dd($this->response);

		// Make sure that the http response code is 200 OK
		$this->assertEquals($this->response->status(), 200);

		// Make sure that the response is a JSON.
		$this->assertTrue($this->response instanceof JsonResponse);
		$json = $this->response->original;

		$rules = [
			"data" => "array"
		];
		$this->assertTrue($this->assertRules($json, $rules));

		$rules = [
			"id" => "numeric",
			"item_id" => "numeric",
			"is_completed" => ["boolean", function ($value) {
				return $value === true;
			}],
			"checklist_id" => ["numeric", function ($value) use ($items) {
				return $items["checklist_id"] == $value;
			}]
		];

		foreach ($json["data"] as $item) {
			$this->assertTrue($this->assertRules($item, $rules));
		}

	}

	/**
	 * @return array
	 */
	public function itemsToBeCompleted(): array
	{
		return [
			[
				[
					"checklist_id" => 1,
					"item_id" => [1, 2, 3]
				]
			]
		];
	}
}
