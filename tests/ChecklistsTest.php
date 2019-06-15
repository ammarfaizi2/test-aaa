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

		// Make sure that the response is a JSON.
		$this->assertTrue($this->response instanceof JsonResponse);
		$json = $this->response->original;

		// We don't need items attribute anymore.
		unset($checklist["data"]["attributes"]["items"]);

		// Set ID with
		$checklist["data"]["attributes"]["id"] = $json["data"]["id"];

		// Check the data in database.
		$this->seeInDatabase('checklists', $checklist["data"]["attributes"]);

		// Make sure that the JSON has the same pattern and data type with
		// https://kw-checklist.docs.stoplight.io/api-reference/checklists/post-checklists

		$this->assertTrue(isset($json["data"]["type"]) && is_string($json["data"]["type"]));
		$this->assertTrue(isset($json["data"]["id"]) && is_numeric($json["data"]["id"]));
		$this->assertTrue(
			isset($json["data"]["attributes"]["object_domain"]) &&
			is_string($json["data"]["attributes"]["object_domain"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["object_id"]) &&
			is_string($json["data"]["attributes"]["object_id"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["task_id"]) &&
			is_string($json["data"]["attributes"]["task_id"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["description"]) &&
			is_string($json["data"]["attributes"]["description"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["is_completed"]) &&
			$json["data"]["attributes"]["is_completed"] === false
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["due"]) &&
			is_string($json["data"]["attributes"]["due"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["urgency"]) &&
			is_numeric($json["data"]["attributes"]["urgency"])
		);
		$this->assertTrue(
			// isset doesn't work with null value
			// so here we use array_key_exists instead.
			array_key_exists("completed_at", $json["data"]["attributes"]) &&
			is_null($json["data"]["attributes"]["completed_at"])
		);
		$this->assertTrue(
			array_key_exists("updated_by", $json["data"]["attributes"]) &&
			is_null($json["data"]["attributes"]["updated_by"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["created_by"]) &&
			is_numeric($json["data"]["attributes"]["created_by"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["created_at"]) &&
			is_string($json["data"]["attributes"]["created_at"])
		);
		$this->assertTrue(
			isset($json["data"]["attributes"]["updated_at"]) &&
			is_string($json["data"]["attributes"]["updated_at"])
		);
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

		// Make sure that the response is a JSON.
		$this->assertTrue($this->response instanceof JsonResponse);

		$json = $this->response->original;

		$this->assertTrue(isset($json["data"]["type"]) && is_string($json["data"]["type"]));
		$this->assertTrue(isset($json["data"]["id"]) && is_numeric($json["data"]["id"]));
		$this->assertEquals($json["data"]["id"], $id);

		dd($this->response);
		$id++;
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
