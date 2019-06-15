<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Item;
use App\Checklist;
use Illuminate\Http\Request;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get("/checklists/{checklistId}", function ($checklistId) {
	try {
		if ($r = Checklist::find($checklistId)) {
			$ret = [
				"data" => [
					"type" => "checklists",
					"id" => $r->id,
					"attributes" => $r->setAppends(
						[
							"completed_at",
							"is_completed",
							"last_update_by"
						]
					)->toArray(),
					"links" => [
						"self" => sprintf("%s/api/v1/checklists/%d", env("APP_URL"), $r->id)
					]
				]
			];
			$ret["data"]["id"] = $ret["data"]["attributes"]["id"];
			unset($ret["data"]["attributes"]["id"]);
			return response()->json($ret, 200);
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->post('/checklists', function (Request $request) {
	try {
		$this->validate($request, [
	        'data' => 'required',
	        'data.attributes' => 'required',
	        'data.attributes.object_domain' => 'required',
	        'data.attributes.object_id' => 'required',
	        'data.attributes.description' => 'required',
	        'data.attributes.due' => 'date',
	        'data.attributes.items' => 'required|array'
	    ]);
		$data = $request->json()->all();

		if (count($data["data"]["attributes"]["items"]) < 1) {
			return response()->json(
				["status" => "400", "error" => "A checklist must have at least 1 item"], 400);
		}

		$checklist = new Checklist();
		foreach (['object_domain', 'object_id', 'due', 'urgency', 'description', 'task_id'] as $k) {
			$checklist->{$k} = isset($data["data"]["attributes"][$k]) ? 
				$data["data"]["attributes"][$k] : null;
		}

		$checklist->created_by = Auth::user()->id;
		$checklist->save();
		$items = [];
		foreach ($data["data"]["attributes"]["items"] as $item) {
			$itemObj = new Item();
			$itemObj->checklist_id = $checklist->id;
			$itemObj->name = $item;
			$itemObj->due = $data["data"]["attributes"]["due"];
			$itemObj->urgency = $data["data"]["attributes"]["urgency"];

			// I don't know where does 123 come from.
			// I just have read the documetation, but couldn't it.
			// Ref: https://kw-checklist.docs.stoplight.io/api-reference/items/get-checklist-item-details
			// This is too vague, so I set it to 123 for temporary.
			$itemObj->assignee_id = 123;

			$itemObj->task_id = $data["data"]["attributes"]["task_id"];
			$itemObj->save();
		}
		unset($items, $item, $itemObj);
		$ret = [
			"data" => [
				"type" => "checklists",
				"id" => $checklist->id,
				"attributes" => $checklist->setAppends(
					[
						"completed_at",
						"is_completed",
						"updated_by"
					]
				)->toArray(),
				"links" => [
					"self" => sprintf("%s/api/v1/checklists/%d", env("APP_URL"), $checklist->id)
				]
			]
		];
		$ret["data"]["id"] = $ret["data"]["attributes"]["id"];
		unset($ret["data"]["attributes"]["id"]);
		return response()->json($ret, 200);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}	
});
