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

		dd($this->response);

		$id++;
	}
}
