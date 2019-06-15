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
	        'data.attributes.due' => 'date'
	    ]);
		$data = $request->json()->all();
		$checklist = new Checklist();
		foreach (['object_domain', 'object_id', 'due', 'urgency', 'description', 'task_id'] as $k) {
			$checklist->{$k} = isset($data["data"]["attributes"][$k]) ? 
				$data["data"]["attributes"][$k] : null;
		}
		$checklist->created_by = Auth::user()->id;
		$checklist->save();
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
