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

$router->get("/", function () use ($router) {
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

$router->delete("/checklists/{checklistId}", function ($checklistId, Request $request) {
	try {
		if ($r = Checklist::find($checklistId)) {
			$r->delete();
			return response(null, 204);
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->patch("/checklists/{checklistId}", function ($checklistId, Request $request) {
	try {
		$this->validate($request, [
	        "data" => "required|array",
	        "data.type" => "required",
	        "data.id" => "required|integer",
	        "data.attributes" => "required|array",
	        "data.attributes.object_domain" => "required|string",
	        "data.attributes.object_id" => "required",
	        "data.attributes.description" => "required|string",
	    ]);
		if ($r = Checklist::find($checklistId)) {
			$data = $request->json()->all();
			$r->object_domain = $data["data"]["attributes"]["object_domain"];
			$r->object_id = $data["data"]["attributes"]["object_id"];
			$r->description = $data["data"]["attributes"]["description"];
			$r->due = $data["data"]["attributes"]["due"];
			foreach (["due", "urgency"] as $key) {
				if (array_key_exists($key, $data["data"]["attributes"])) {
					$r->{$key} = $data["data"]["attributes"][$key];
				}
			}
			$r->update();
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


// Get list of checklists.
$router->get("/checklists", function (Request $request) {
	try {
		$limit = 10;
		$offset = 0;
		$sort = null;
		$sortType = "ASC";
		$q = $request->all();
		$checklist = new Checklist;

		$availableFields = [
			"object_domain", "object_id", "task_id", "description", "due",
			"urgency", "updated_by", "created_by", "created_at", "updated_at"
		];

		if (isset($q["page"]["limit"]) && is_numeric($q["page"]["limit"])) {
			$limit = (int)$q["page"]["limit"];
		}

		if (isset($q["page"]["offset"]) && is_numeric($q["page"]["offset"])) {
			$offset = (int)$q["page"]["offset"];
		}

		if (isset($q["sort"]) && is_string($q["sort"]) && isset($q["sort"][0])) {
			
			if ($q["sort"][0] === "-") {
				$sortType = "DESC";
				$q["sort"] = substr($q["sort"], 1);
			}

			$sort = $q["sort"];

			if (!in_array($sort, $availableFields)) {
				return response()->json(["status" => 400, 
					"error" => sprintf("Sort error: %s is not a valid field", $sort)], 400);
			}
		}

		if (isset($q["filter"])) {
			foreach ($q["filter"] as $key => $value) {
				if (!in_array($key, $availableFields)) {
					return response()->json(["status" => 400, 
						"error" => sprintf("Filter error: %s is not a valid field", $q["sort"])], 400);
				}
				foreach ($value as $vkey => $vvalue) {
					$checklist->setInternalWhereClause($key, $vkey, $vvalue);
				}
			}

			try {
				$checklist->buildInternalWhereClause();	
			} catch (Exception $e) {
				return response()->json(
					["status" => "400", "error" => sprintf("Filter error: %s", $e->getMessage())], 400);
			}
		}

		$checklist->setInternalLimit($limit);
		$checklist->setInternalOffset($offset);
		is_string($sort) and $checklist->setInternalSort($sort, $sortType);
		$checklist->setInternalQueryString($q);

		$data = $checklist->getListOfChecklists();
		$ret = [
			"meta" => [
				"count" => count($data),
				"total" => $checklist->getTotalChecklist()
			],
			"links" => [
				"first" => $checklist->getFirstLink(),
				"last" => $checklist->getLastLink(),
				"next" => $checklist->getNextLink(),
				"prev" => $checklist->getPrevLink()
			],
			"data" => $data
		];
		unset($data);

		// // Debug here
		// dd($ret);

		return response()->json($ret, 200);
	} catch (Error $e) {

		// Debug here
		// dd($e->getMessage());

		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

// This creates a Checklist object.
$router->post("/checklists", function (Request $request) {
	try {
		$this->validate($request, [
	        "data" => "required",
	        "data.attributes" => "required|array",
	        "data.attributes.object_domain" => "required",
	        "data.attributes.object_id" => "required",
	        "data.attributes.description" => "required",
	        "data.attributes.due" => "date",
	        "data.attributes.items" => "required|array"
	    ]);
		$data = $request->json()->all();

		if (count($data["data"]["attributes"]["items"]) < 1) {
			return response()->json(
				["status" => "400", "error" => "A checklist must have at least 1 item"], 400);
		}

		$checklist = new Checklist();
		foreach (["object_domain", "object_id", "due", "urgency", "description", "task_id"] as $k) {
			$checklist->{$k} = isset($data["data"]["attributes"][$k]) ? 
				$data["data"]["attributes"][$k] : null;
		}

		$checklist->created_by = Auth::user()->id;
		$checklist->save();
		$items = [];
		foreach ($data["data"]["attributes"]["items"] as $kkk => $item) {
			$itemObj = new Item();
			$itemObj->item_id = $kkk + 1;
			$itemObj->checklist_id = $checklist->id;
			$itemObj->name = $item;
			$itemObj->due = $data["data"]["attributes"]["due"];
			$itemObj->urgency = $data["data"]["attributes"]["urgency"];

			// I don"t know where does 123 come from.
			// I just have read the documetation, but couldn"t it.
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
		return response()->json($ret, 201);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}	
});


$router->get("/checklists/{checklistId}/items", function ($checklistId, Request $request) {
	try {
		if ($checklist = Checklist::find($checklistId)) {
			$ret = [
				"data" => [
					"type" => "checklists",
					"id" => $checklist->id,
					"attributes" => $checklist->setAppends(
						[
							"completed_at",
							"is_completed",
							"last_update_by"
						]
					)->toArray(),
					"links" => [
						"self" => sprintf("%s/api/v1/checklists/%d", env("APP_URL"), $checklist->id)
					]
				]
			];
			foreach ($checklist->items as $item) {
				$ret["data"]["attributes"]["items"][] = $item->toArray();
			}
			return response()->json($ret, 200);
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->get("/checklists/{checklistId}/items/{itemId}", function ($checklistId, $itemId, Request $request) {
	try {
		if ($checklist = Checklist::find($checklistId)) {
			if ($item = $checklist->items()->where("item_id", $itemId)->first()) {
				$ret = [
					"data" => [
						"type" => "checklists",
						"id" => $checklist->id,
						"attributes" => $checklist->setAppends(
							[
								"completed_at",
								"is_completed",
								"last_update_by"
							]
						)->toArray(),
						"links" => [
							"self" => sprintf("%s/api/v1/checklists/%d", env("APP_URL"), $checklist->id)
						]
					]
				];
				$ret["data"]["attributes"]["item"] = $item->toArray();
				$ret["data"]["id"] = $ret["data"]["attributes"]["id"];
				unset($ret["data"]["attributes"]["id"]);
				return response()->json($ret, 200);
			}
		}

		// // Debug here
		// dd($checklistId, $itemId);

		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->post("/checklists/{checklistId}/complete", function ($checklistId, Request $request) {
	try {
		if ($checklist = Checklist::find($checklistId)) {
			$this->validate($request, ["data" => "required|array"]);
			$req = $request->json()->all();
			$ret = ["data" => []];
			foreach ($req["data"] as $item) {
				if (
					isset($item["item_id"]) &&
					($item = $checklist->items()
					->where("item_id", $item["item_id"])
					->where("checklist_id", $checklistId)
					->first())
				) {
					if ($itemObj = Item::find($item->id)) {
						$itemObj->completed_at = date("Y-m-d H:i:s");
						$itemObj->update();
						$ret["data"][] = [
							"id" => $itemObj->id,
							"item_id" => $itemObj->item_id,
							"is_completed" => true,
							"checklist_id" => $checklistId
						];
					}
				}
			}
			return response()->json($ret, 200);
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->post("/checklists/{checklistId}/incomplete", function ($checklistId, Request $request) {
	try {
		if ($checklist = Checklist::find($checklistId)) {
			$this->validate($request, ["data" => "required|array"]);
			$req = $request->json()->all();
			$ret = ["data" => []];
			foreach ($req["data"] as $item) {
				if (
					isset($item["item_id"]) &&
					($item = $checklist->items()
					->where("item_id", $item["item_id"])
					->where("checklist_id", $checklistId)
					->first())
				) {
					if ($itemObj = Item::find($item->id)) {
						$itemObj->completed_at = date("Y-m-d H:i:s");
						$itemObj->update();
						$ret["data"][] = [
							"id" => $itemObj->id,
							"item_id" => $itemObj->item_id,
							"is_completed" => false,
							"checklist_id" => $checklistId
						];
					}
				}
			}
			return response()->json($ret, 200);
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->post("/checklists/{checklistId}/items", function ($checklistId, Request $request) {
	try {
		if ($checklist = Checklist::find($checklistId)) {
			$this->validate($request, [
				"data" => "required|array",
				"data.attributes" => "required|array",
				"data.attributes.description" => "required|string",
				"data.attributes.urgency" => "integer",
				"data.attributes.assignee_id" => "nullable|integer",
				"data.attributes.is_completed" => "bool",
				"data.attributes.completed_at" => "date",
				"data.attributes.due" => "date",
				"data.attributes.task_id" => "nullable|integer"
			]);

			$data = $request->json()->all();

			$item = new Item;

			$latestItemId = Item::where("checklist_id", $checklistId)
				->orderBy("item_id", "desc")
				->first();

			if ($latestItemId) {
				$latestItemId = $latestItemId->item_id;
			} else {
				$latestItemId = 0;
			}

			if (isset($data["data"]["attributes"]["is_completed"]) && $data["data"]["attributes"]["is_completed"]) {
				if (isset($data["data"]["attributes"]["completed_at"])) {
					$item->completed_at = date("Y-m-d H:i:s", strtotime($data["data"]["attributes"]["completed_at"]));
				} else {
					$item->completed_at = date("Y-m-d H:i:s");
				}
			}

			$item->task_id = $data["data"]["attributes"]["task_id"] ?? null;
			$item->assignee_id = $data["data"]["attributes"]["assignee_id"] ?? null;
			$item->checklist_id = $checklistId;
			$item->item_id = $latestItemId + 1;
			$item->name = $data["data"]["attributes"]["description"];
			$item->due = $data["data"]["attributes"]["due"] ?? null;
			$item->urgency = $data["data"]["attributes"]["urgency"];
			$item->save();

			$ret = [
				"data" => [
					"type" => "checklists",
					"id" => $checklistId,
					"attributes" => $item->toArray()
				]
			];

			unset($ret["data"]["attributes"]["id"]);
			return response()->json($ret, 200);
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->patch("/checklists/{checklistId}/items/{itemId}", function ($checklistId, $itemId, Request $request) {
	try {
		if ($checklist = Checklist::find($checklistId)) {

			$this->validate($request, [
				"data" => "required|array",
				"data.attributes" => "required|array",
				"data.attributes.description" => "required|string",
				"data.attributes.urgency" => "integer",
				"data.attributes.assignee_id" => "nullable|integer",
				"data.attributes.is_completed" => "bool",
				"data.attributes.completed_at" => "date",
				"data.attributes.due" => "date",
				"data.attributes.task_id" => "nullable|integer"
			]);

			$data = $request->json()->all();

			if ($itemObj = Item::find($itemId)) {
				$itemObj->completed_at = date("Y-m-d H:i:s");

				array_key_exists("task_id", $data["data"]["attributes"]) and
					$itemObj->task_id = $data["data"]["attributes"]["task_id"];

				array_key_exists("assignee_id", $data["data"]["attributes"]) and
					$itemObj->assignee_id = $data["data"]["attributes"]["assignee_id"];

				array_key_exists("due", $data["data"]["attributes"]) and
					$itemObj->due = date("Y-m-d H:i:s", strtotime($data["data"]["attributes"]["due"]));

				array_key_exists("urgency", $data["data"]["attributes"]) and
					$itemObj->urgency = $data["data"]["attributes"]["urgency"];

				$itemObj->name = $data["data"]["attributes"]["description"];

				$itemObj->update();

				$ret = ["data" => [
					"type" => "checklists",
					"id" => $checklistId,
					"attributes" => $itemObj->toArray(),
					"links" => [
						"self" => sprintf("%s/checklists/%d", env("API_URL"), $checklistId)
					]
				]];

				foreach (["created_at", "updated_at", "completed_at", "due"] as $key) {
					if (isset($ret["data"]["attributes"][$key])) {
						$ret["data"]["attributes"][$key] = date("c",
							strtotime($ret["data"]["attributes"][$key]));
					}
				}

				return response()->json($ret, 200);
			}
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

$router->delete("/checklists/{checklistId}/items/{itemId}", function ($checklistId, $itemId) {
	try {
		if ($checklist = Checklist::find($checklistId)) {
			if ($itemObj = Item::find($itemId)) {
				$itemObj->delete();
				return response(null, 204);
			}
		}
		return response()->json(["status" => "404", "error" => "Not Found"], 404);
	} catch (Error $e) {
		return response()->json(["status" => "500", "error" => "Server Error"], 500);
	}
});

