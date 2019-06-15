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

	// Please note that "@depends" is different with "@Depends"
	// You can see the discussion about this issue here
	// https://github.com/sebastianbergmann/phpunit/issues/2647#issuecomment-486186376

	/**
	 * @dataProvider \Tests\ChecklistsTest::checklistsToBeCreated
	 * @Depends \Tests\ChecklistsTest::testGetListOfChecklists
	 * @param array $checklist
	 * @return void
	 */
	public function testListAllItemsInGivenChecklists(array $checklist): void
	{
		static $id = 1;

		$this->json('DELETE', sprintf('/checklists/%d/items', $id), [], ['Authorization' => TEST_TOKEN]);

		dd($this->response);

		$id++;
	}
}
