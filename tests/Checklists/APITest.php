<?php

namespace tests\Checklists;

define("TEST_TOKEN", "1LQxW0CjRz8ZaY1GvOxoCuHlNS7oecmQxEYJ4V/Fpd+WmfeUOwRVhw==");

use TestCase;

class APITest extends TestCase
{
	/**
	 * @dataProvider checklistsToBeCreated
	 * @param array $checklist
	 * @return void
	 */
	public function testCreateChecklist($checklist): void
	{
		$checklist = ["data" => ["attributes" => $checklist]];
		$this->json('POST', '/checklists', $checklist, ['Authorization' => TEST_TOKEN]);

		unset($checklist["data"]["attributes"]["items"]);
		$checklist["data"]["attributes"]["id"] = $this->response->original["data"]["id"];

		$this->seeInDatabase('checklists', $checklist["data"]["attributes"]);
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