<?php

namespace Tests;

use DB;
use TestCase;
use Illuminate\Http\JsonResponse;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @package \Tests
 */
class ChecklistsTest extends TestCase
{
	/**
	 * @const TEST_TOKEN
	 * @see tests/TestCase.php
	 */

	use Utils\DataRules;

	/**
	 * @return void
	 */
	public function testCleanUp(): void
	{
		$pdo = DB::getPdo();

		$queries = [
			"SET @@foreign_key_checks = 0;",
			"TRUNCATE TABLE items;",
			"TRUNCATE TABLE checklists;"
		];

		foreach ($queries as $query) {
			$this->assertTrue($pdo->prepare($query)->execute());
		}
	}

	/**
	 * @depends testCleanUp
	 * @dataProvider checklistsToBeCreated
	 * @param array $checklist
	 * @return void
	 */
	public function testCreateChecklist(array $checklist): void
	{
		$checklist = ["data" => ["attributes" => $checklist]];
		$this->json('POST', '/checklists', $checklist, ['Authorization' => TEST_TOKEN]);

		// Make sure that the http response code is 200 OK
		$this->assertEquals($this->response->status(), 200);

		// Make sure that the response is a JSON.
		$this->assertTrue($this->response instanceof JsonResponse);
		$json = $this->response->original;

		// We don't need items attribute anymore.
		unset($checklist["data"]["attributes"]["items"]);

		// Set ID with the response value.
		$checklist["data"]["attributes"]["id"] = $json["data"]["id"];

		// Check the data in database.
		$this->seeInDatabase('checklists', $checklist["data"]["attributes"]);

		// Make sure that the JSON has the same pattern and data type with
		// https://kw-checklist.docs.stoplight.io/api-reference/checklists/post-checklists
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
			"data.attributes.updated_by" => "NULL",
			"data.attributes.created_by" => "numeric",
			"data.attributes.created_at" => "string",
			"data.attributes.updated_at" => "string",
			"data.links" => "array",
			"data.links.self" => ["string", function (string $value) {
				return filter_var($value, FILTER_VALIDATE_URL);
			}]
		];

		$this->assertTrue($this->assertRules($json, $rules));
	}

	/**
	 * @depends testCreateChecklist
	 * @return void
	 */
	public function testGetListOfChecklists(): void
	{
		// Without any filter.
		$this->json('GET', '/checklists', [], ['Authorization' => TEST_TOKEN]);

		dd($this->response);
	}

	/**
	 * @depends testCreateChecklist
	 * @dataProvider checklistsToBeCreated
	 * @param array $checklist
	 * @return void
	 */
	public function testGetChecklist(array $checklist): void
	{
		static $id = 1;
		$this->json('GET', sprintf('/checklists/%d', $id), [], ['Authorization' => TEST_TOKEN]);

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
			"data.links" => "array",
			"data.links.self" => ["string", function (string $value) {
				return filter_var($value, FILTER_VALIDATE_URL);
			}]
		];

		$this->assertTrue($this->assertRules($json, $rules));

		$id++;
	}

	/**
	 * @depends testGetChecklist
	 * @dataProvider checklistsToBeUpdated
	 * @param array $checklist
	 * @return void
	 */
	public function testUpdateChecklist(array $checklist): void
	{
		static $id = 1;
		$checklist = [
			"data" => [
				"type" => "checklists",
				"id" => $id,
				"attributes" => $checklist,
				"links" => [
					"self" => sprintf("%s/checklists/%d", env("APP_URL"), $id)
				]
			]
		];
		$this->json('PATCH', sprintf('/checklists/%d', $id), $checklist, ['Authorization' => TEST_TOKEN]);

		// // Debug here
		// dd($this->response);

		// Make sure that the http response code is 200 OK
		$this->assertEquals($this->response->status(), 200);

		// Make sure that the response is a JSON.
		$this->assertTrue($this->response instanceof JsonResponse);

		$json = $this->response->original;


		// https://kw-checklist.docs.stoplight.io/api-reference/checklists/put-checklists
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
			"data.links" => "array",
			"data.links.self" => ["string", function (string $value) {
				return filter_var($value, FILTER_VALIDATE_URL);
			}]
		];

		$this->assertTrue($this->assertRules($json, $rules));

		$id++;
	}

	/**
	 * @depends testUpdateChecklist
	 * @dataProvider checklistsToBeCreated
	 * @param array $checklist
	 * @return void
	 */
	public function testDeleteChecklist(): void
	{
		static $id = 1;
		$this->json('DELETE', sprintf('/checklists/%d', $id), [], ['Authorization' => TEST_TOKEN]);
		$this->assertEquals($this->response->status(), 204);
		$id++;
	}

	/**
	 * @return array
	 */
	public function checklistsToBeUpdated(): array
	{
		return [
			[
				[
					"object_domain" => "contact",
					"object_id" => "1",
					"due" => "2019-01-25T07:50:14+00:00",
					"urgency" => 1,
					"description" => "Need to verify this guy house. (updated)",
					"task_id" => "123"
				]
			]
		];
	}

	/**
	 * @return array
	 */
	public function checklistsToBeCreated(): array
	{
		return [
			[
				[
					"object_domain" => "contact",
					"object_id" => "1",
					"due" => "2019-01-25T07:50:14+00:00",
					"urgency" => 1,
					"description" => "Need to verify this guy house.",
					"items" => [
						"Visit his house",
						"Capture a photo",
						"Meet him on the house"
					],
					"task_id" => "123"
				]
			]
		];
	}
}
