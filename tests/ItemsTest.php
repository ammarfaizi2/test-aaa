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
		$this->json("GET", sprintf("/checklists/%d/items", $id), [], ["Authorization" => TEST_TOKEN]);

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
			$this->json("GET", sprintf("/checklists/%d/items/%d", $id, $k + 1), [], ["Authorization" => TEST_TOKEN]);

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

		$this->json("POST", sprintf("/checklists/%d/complete", $items["checklist_id"]),
			$data, ["Authorization" => TEST_TOKEN]);

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
	 * @depends testCompleteItem
	 * @dataProvider itemsToBeCompleted
	 * @param array $items
	 * @return void
	 */
	public function testIncompleteItem(array $items): void
	{
		$data = ["data" => []];

		foreach ($items["item_id"] as $item) {
			$data["data"][] = [
				"item_id" => $item
			];
		}

		$this->json("POST", sprintf("/checklists/%d/incomplete", $items["checklist_id"]),
			$data, ["Authorization" => TEST_TOKEN]);

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
				return $value === false;
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
	 * @depends testIncompleteItem
	 * @dataProvider newItemsToBeCreated
	 * @param int $checklistId
	 * @param array $items
	 * @return void
	 */
	public function testCreateItem(int $checklistId, array $items): void
	{
		foreach ($items as $item) {
			$data = [
				"data" => [
					"attributes" => $item
				]
			];

			$this->json("POST", sprintf("/checklists/%d/items", $checklistId), $data,
				["Authorization" => TEST_TOKEN]);

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
				"data.attributes.assignee_id" => "numeric",
				"data.attributes.checklist_id" => "numeric",
				"data.attributes.item_id" => "numeric",
				"data.attributes.name" => "string",
				"data.attributes.due" => "string",
				"data.attributes.urgency" => "numeric",
				"data.attributes.created_at" => "string",
				"data.attributes.updated_at" => "string"
			];

			// dd($json);

			$this->assertTrue($this->assertRules($json, $rules));

			// Date time format ISO 8601
			foreach (["created_at", "updated_at", "completed_at", "due"] as $key) {
				if (isset($json["data"]["attributes"][$key])) {
					$this->assertTrue((bool)
						// \S means PCRE study.
						preg_match(
							"/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$/S",
							$json["data"]["attributes"][$key]
						)
					);
				}
			}

		}
	}

	/**
	 * @depends testCreateItem
	 * @dataProvider itemsToBeUpdated
	 * @param int $checklistId
	 * @param array $items
	 * @return void
	 */
	public function testUpdateItem(int $checklistId, array $items): void
	{
		foreach ($items as $item) {
			$data = [
				"data" => [
					"attributes" => $item["attributes"]
				]
			];

			// dd($data);

			$this->json("PATCH", sprintf("/checklists/%d/items/%d", $checklistId, $item["item_id"]), $data,
				["Authorization" => TEST_TOKEN]);

			// // Debug here
			// dd($this->response);

			// Make sure that the http response code is 200
			$this->assertEquals($this->response->status(), 200);

			// Make sure that the response is a JSON.
			$this->assertTrue($this->response instanceof JsonResponse);
			$json = $this->response->original;

			$rules = [
				"data" => "array",
				"data.type" => "string",
				"data.id" => "numeric",
				"data.attributes.assignee_id" => "numeric",
				"data.attributes.checklist_id" => "numeric",
				"data.attributes.item_id" => "numeric",
				"data.attributes.name" => "string",
				"data.attributes.due" => "string",
				"data.attributes.urgency" => "numeric",
				"data.attributes.created_at" => "string",
				"data.attributes.updated_at" => "string"
			];

			// dd($json);

			$this->assertTrue($this->assertRules($json, $rules));

			// Date time format ISO 8601
			foreach (["created_at", "updated_at", "completed_at", "due"] as $key) {
				if (isset($json["data"]["attributes"][$key])) {
					$this->assertTrue((bool)
						// \S means PCRE study.
						preg_match(
							"/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$/S",
							$json["data"]["attributes"][$key]
						)
					);
				}
			}
		}
	}

	/**
	 * @depends testUpdateItem
	 * @dataProvider itemsToBeBulkUpdated
	 * @param int $checklistId
	 * @param array $items
	 * @return void
	 */
	public function testBulkUpdateItem(int $checklistId, array $items): void
	{
		foreach ($items as $item) {
			$data = [
				"data" => [
					"attributes" => $item["attributes"]
				]
			];

			// dd($data);

			$this->json("POST", sprintf("/checklists/%d/items/_bulk", $checklistId, $item["item_id"]), $data,
				["Authorization" => TEST_TOKEN]);

			// // Debug here
			// dd($this->response);

			// Make sure that the http response code is 200 OK
			$this->assertEquals($this->response->status(), 200);
		}
	}

	/**
	 * @depends testBulkUpdate
	 * @dataProvider itemsToBeUpdated
	 * @param int $checklistId
	 * @param array $items
	 * @return void
	 */
	public function testDeleteItem(int $checklistId, array $items): void
	{

		foreach ($items as $item) {
			$data = [
				"data" => [
					"attributes" => $item["attributes"]
				]
			];

			// dd($data);

			$this->json("DELETE", sprintf("/checklists/%d/items/%d", $checklistId, $item["item_id"]), $data,
				["Authorization" => TEST_TOKEN]);

			// // Debug here
			// dd($this->response);

			// Make sure that the http response code is 204 OK
			$this->assertEquals($this->response->status(), 204);
		}
	}

	/**
	 * @return array
	 */
	public function itemsToBeBulkUpdated(): array
	{
		return [
			[
				1,
				[
					[
						"item_id" => 1,
						"attributes" =>  [
							"description" => "test bulk update 12334535345",
							"due" => "2019-03-19 10:33:21",
							"urgency" => 1,
							"assignee_id" => 123
						]
					],
					[
						"item_id" => 2,
						"attributes" =>  [
							"description" => "test bulk update 12312",
							"due" => "2019-03-19 10:33:21",
							"urgency" => 10,
							"assignee_id" => 123
						]
					]
				]
			]
		];
	}

	/**
	 * @return array
	 */
	public function itemsToBeUpdated(): array
	{
		return [
			[
				1,
				[
					[
						"item_id" => 1,
						"attributes" =>  [
							"description" => "test update 123123123",
							"due" => "2019-03-19 10:33:21",
							"urgency" => 2,
							"assignee_id" => 123
						]
					]
				]
			]
		];
	}

	/**
	 * @return array
	 */
	public function newItemsToBeCreated(): array
	{
		return [
			[
				1,
				[
					[
						"description" => "test item abc",
						"due" => "2019-01-19 18:34:51",
						"urgency" => 2,
						"assignee_id" => 123
					]
				]
			]
		];
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
